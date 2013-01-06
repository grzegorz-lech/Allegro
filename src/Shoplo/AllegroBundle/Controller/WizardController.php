<?php

namespace Shoplo\AllegroBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use InvalidArgumentException;
use Shoplo\AllegroBundle\Entity\Item;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Symfony\Component\HttpFoundation\Request;
use Shoplo\AllegroBundle\Form\Wizard;
use Shoplo\AllegroBundle\WebAPI\Allegro;

class WizardController extends Controller
{
    /**
     * @Secure(roles="ROLE_ADMIN")
     */
    public function indexAction(Request $request)
    {
        $ids = $request->query->get('product', array());
        $ids = !is_array($ids) ? explode(',', $ids) : $ids;
        if (empty($ids)) {
            throw $this->createNotFoundException('Product IDs missing');
        }

        // Informacje o produktach
        $shoplo   = $this->get('shoplo');
        $variants = $products = array();

        foreach ($ids as $id) {
            $product = $shoplo->get('products', $id);

            // Kategorie
            $categoryIDs = array();

            foreach ($product['categories'] as $category) {
                $categoryIDs[] = $category['id'];
            }

            $categories = $this->getDoctrine()
                ->getRepository('ShoploAllegroBundle:Category')
                ->findBy(array('shop_id' => $this->getUser()->getShopId(), 'shoplo_id' => $categoryIDs));

            if (count($categoryIDs) !== count($categories)) {
                return $this->redirect($this->generateUrl('shoplo_allegro_settings_mapping'));
            }

            foreach ($product['variants'] as $variant) {
                $variant['categories']    = $categories;
                $variant['thumbnail']     = $product['thumbnail'];
                $variants[$variant['id']] = $variant;
            }

            $products[] = $product;
        }

        $wizard = new Wizard();
        $extras = array();
        $form   = $this->createFormBuilder($wizard)
            ->add('title', 'text') // TODO: Ustawienie maksymalnej długości LIMIT_ALLEGRO-MAX(nazwa_wariantu)
            ->add('description', 'textarea')
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->bind($request);

            if ($form->isValid()) {
                $wizard = $form->getData();
                $em     = $this->get('doctrine')->getManager();

                // TODO: Profil z listy
                $profile = $this->getDoctrine()
                    ->getRepository('ShoploAllegroBundle:Profile')
                    ->findOneBy(array('user_id' => $this->getUser()->getId()));

                /** @var $allegro Allegro */
                $allegro = $this->get('allegro');
                $allegro->login($this->getUser());

                foreach ($products as $product) {
                    foreach ($product['variants'] as $variant) {
                        $categoryId = $_POST['category'][$variant['id']];
                        $fields     = $wizard->export($profile, $product, $variant, $categoryId);

                        // Obsługa dodatkowych (wymaganych) pól Allegro
                        $extraFields = $allegro->getCategoryFields($categoryId);

                        if (isset($_POST['extras'][$variant['id']])) {
                            foreach ($_POST['extras'][$variant['id']] as $key => $value) {
                                $field = $extraFields[$key];

                                switch ($field['sell-form-res-type']) {
                                    case 1: // string
                                        $fields[] = Wizard::createField($key, (string)$value);
                                        break;
                                    case 2: // integer
                                        $fields[] = Wizard::createField($key, (int)$value);
                                        break;
                                    case 3: // float
                                        $fields[] = Wizard::createField($key, (float)$value);
                                        break;
                                    case 7: // image (base64Binary)
                                        $fields[] = Wizard::createField($key, $value, true);
                                        break;
                                    case 9: // datetime (Unix time)
                                        $fields[] = Wizard::createField($key, $value);
                                        break;
                                    case 13: // date
                                        $fields[] = Wizard::createField($key, $value);
                                        break;
                                }
                            }
                        }

                        $missingFields = Allegro::getMissingFields($fields, $extraFields);

                        if (!empty($missingFields)) {
                            $extras[$categoryId] = $missingFields;
                        }

                        if (!empty($extras)) {
                            continue;
                        }

                        $itemId = $this->createAuction($fields);

                        $item = new Item();
                        $days = array(3, 5, 7, 10, 14, 30);
                        $item
                            ->setId($itemId)
                            ->setVariantId($variant['id'])
                            ->setProductId($product['id'])
                            ->setPrice($variant['price'])
                            ->setQuantity($variant['quantity'])
                            ->setStartAt(new \DateTime('now'))
                            ->setEndAt(new \DateTime('+' . $days[$profile->getDuration()] . ' days'));

                        $em->persist($item);
                    }
                }

                $em->flush();

                if (empty($extras)) {
                    return $this->redirect($this->generateUrl('shoplo_allegro_homepage'));
                }
            }
        }

        if (!empty($extras)) {
            foreach ($extras as $categoryId => $fields) {
                foreach ($fields as $k => $field) {
                    switch ($field['sell-form-type']) {
                        case 4: // combobox
                            $field = array(
                                'id'      => $field['sell-form-id'],
                                'label'   => $field['sell-form-title'],
                                'title'   => $field['sell-form-field-desc'],
                                'options' => array_combine(
                                    explode('|', $field['sell-form-opts-values']),
                                    explode('|', $field['sell-form-desc'])
                                ),
                            );
                            break;

                        default:
                            throw new InvalidArgumentException;
                    }

                    $extras[$categoryId][$k] = $field;
                }
            }
        }

        return $this->render(
            'ShoploAllegroBundle::wizard.html.twig',
            array(
                'form'     => $form->createView(),
                'extras'   => $extras,
                'ids'      => $ids,
                'variants' => $variants,
                'products' => $products,
            )
        );
    }

    private function createAuction(array $fields)
    {
        /** @var $allegro Allegro */
        $allegro = $this->get('allegro');
        if (!$allegro->login($this->getUser())) {
            throw new AccessDeniedException();
        }

        $item = $allegro->doNewAuctionExt($allegro->getSession(), $fields);

        return $item['item-id'];
    }
}
