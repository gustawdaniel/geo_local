<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 15.01.17
 * Time: 15:03
 */

namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Place", mappedBy="users", cascade={"persist"})
     */
    private $places;

    /**
     * @return mixed
     */
    public function getPlaces()
    {
        return $this->places->toArray();
    }


    public function removePlace(Place $place)
    {
        $this->places->remove($place);
    }

    /**
     * @param mixed $place
     */
    public function addPlace(Place $place)
    {
        if (!$this->places->contains($place))
        {
            $this->places->add($place);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->places = new ArrayCollection();
    }

    public function setEmail($email)
    {
        $email = is_null($email) ? '' : $email;
        parent::setEmail($email);
        $this->setUsername($email);

        return $this;
    }
}