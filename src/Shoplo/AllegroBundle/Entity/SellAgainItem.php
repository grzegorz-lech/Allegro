<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * SellAgainItem
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Shoplo\AllegroBundle\Entity\SellAgainItemRepository")
 */
class SellAgainItem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="integer")
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="local_id", type="integer")
     */
    private $local_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="integer")
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="duration", type="integer")
     */
    private $duration;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set item_id
     *
     * @param integer $itemId
     * @return SellAgainItem
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;
    
        return $this;
    }

    /**
     * Get item_id
     *
     * @return integer 
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set local_id
     *
     * @param integer $localId
     * @return SellAgainItem
     */
    public function setLocalId($localId)
    {
        $this->local_id = $localId;
    
        return $this;
    }

    /**
     * Get local_id
     *
     * @return integer 
     */
    public function getLocalId()
    {
        return $this->local_id;
    }

    /**
     * Set user_id
     *
     * @param integer $userId
     * @return SellAgainItem
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    
        return $this;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return SellAgainItem
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer
     */
    public function getDuration()
    {
        return $this->duration;
    }
}
