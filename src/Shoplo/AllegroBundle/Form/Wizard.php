<?php

namespace Shoplo\AllegroBundle\Form;

use Symfony\Component\Validator\Constraints as Assert;

class Wizard
{
    /**
     * @Assert\NotNull()
     */
    protected $layout;

    /**
     * @Assert\NotBlank()
     */
    protected $description;

    /**
     * @param int $layout
     */
    public function setLayout($layout)
    {
        $this->layout = $layout;
    }

    /**
     * @return int
     */
    public function getLayout()
    {
        return $this->layout;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }
}
