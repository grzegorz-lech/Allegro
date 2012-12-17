<?php

namespace Shoplo\AllegroBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Deal
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Deal
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="event_id", type="bigint")
     */
    private $event_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="event_type", type="smallint")
     */
    private $event_type;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="event_time", type="datetimetz")
     */
    private $event_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="transaction_id", type="bigint")
     */
    private $transaction_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="seller_id", type="integer")
     */
    private $seller_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint")
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="buyer_id", type="integer")
     */
    private $buyer_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer")
     */
    private $quantity;

    /**
     * @param  int  $id
     * @return Deal
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Identyfikator aktu zakupowego
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Identyfikator zdarzenia
     *
     * @param  integer $eventId
     * @return Deal
     */
    public function setEventId($eventId)
    {
        $this->event_id = $eventId;

        return $this;
    }

    /**
     * Identyfikator zdarzenia
     *
     * @return integer
     */
    public function getEventId()
    {
        return $this->event_id;
    }

    /**
     * Typ zdarzenia
     *
     * @param  integer $eventType
     * @return Deal
     */
    public function setEventType($eventType)
    {
        $this->event_type = $eventType;

        return $this;
    }

    /**
     * Typ zdarzenia
     *
     * @return integer
     */
    public function getEventType()
    {
        return $this->event_type;
    }

    /**
     * Data zapisania zdarzenia w dzienniku
     *
     * @param  \DateTime $eventTime
     * @return Deal
     */
    public function setEventTime($eventTime)
    {
        $this->event_time = $eventTime;

        return $this;
    }

    /**
     * Data zapisania zdarzenia w dzienniku
     *
     * @return \DateTime
     */
    public function getEventTime()
    {
        return $this->event_time;
    }

    /**
     * Identyfikator transakcji
     *
     * @param  integer $transactionId
     * @return Deal
     */
    public function setTransactionId($transactionId)
    {
        $this->transaction_id = $transactionId;

        return $this;
    }

    /**
     * Identyfikator transakcji
     *
     * @return integer
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * Identyfikator sprzedającego
     *
     * @param  integer $sellerId
     * @return Deal
     */
    public function setSellerId($sellerId)
    {
        $this->seller_id = $sellerId;

        return $this;
    }

    /**
     * Identyfikator sprzedającego
     *
     * @return integer
     */
    public function getSellerId()
    {
        return $this->seller_id;
    }

    /**
     * Identyfikator oferty
     *
     * @param  integer $itemId
     * @return Deal
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Identyfikator oferty
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Identyfikator kupującego
     *
     * @param  integer $buyerId
     * @return Deal
     */
    public function setBuyerId($buyerId)
    {
        $this->buyer_id = $buyerId;

        return $this;
    }

    /**
     * Identyfikator kupującego
     *
     * @return integer
     */
    public function getBuyerId()
    {
        return $this->buyer_id;
    }

    /**
     * Informacja o liczbie kupionych przedmiotów w danym akcie zakupowym
     *
     * @param  integer $quantity
     * @return Deal
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Informacja o liczbie kupionych przedmiotów w danym akcie zakupowym
     *
     * @return integer
     */
    public function getQuantity()
    {
        return $this->quantity;
    }
}
