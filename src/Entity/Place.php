<?php
/**
 * Created by PhpStorm.
 * User: daniel
 * Date: 22.01.16
 * Time: 21:20
 */

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="places")
 */
class Place
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="google_id", type="string", nullable=true)
     */
    private $googleId;

    /**
    * @ORM\ManyToMany(targetEntity="User", inversedBy="places")
    * @ORM\JoinTable(name="users_places")
    */
    private $users;

    /** @ORM\Column(name="formatted_address", type="string", nullable=true)  */
    protected $formattedAddress;

    /** @ORM\Column(name="lon", type="float", precision=9, nullable=true)  */
    protected $lon;

    /** @ORM\Column(name="lat", type="float", precision=9, nullable=true)  */
    protected $lat;

    /** @ORM\Column(name="add_at",type="datetime") */
    protected $add_at;

    /** @ORM\Column(name="street_number",type="string", nullable=true) */
    protected $streetNumber;

    /** @ORM\Column(name="route",type="string", nullable=true) */
    protected $route;

    /** @ORM\Column(name="sublocalityLevel1",type="string", nullable=true) */
    protected $sublocalityLevel1;

    /** @ORM\Column(name="locality",type="string", nullable=true) */
    protected $locality;

    /** @ORM\Column(name="administrative_area_level_2",type="string", nullable=true) */
    protected $administrativeAreaLevel2;

    /** @ORM\Column(name="administrative_area_level_1",type="string", nullable=true) */
    protected $administrativeAreaLevel1;

    /** @ORM\Column(name="country",type="string", nullable=true) */
    protected $country;



    public function __construct() {
        $this->users = new ArrayCollection();
        $this->setAddAt(new \DateTime("now"));
    }

    /**
     * @return mixed
     */
    public function getGoogleId()
    {
        return $this->googleId;
    }

    /**
     * @param mixed $googleId
     */
    public function setGoogleId($googleId)
    {
        $this->googleId = $googleId;
    }

    /**
     * @return mixed
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param mixed $user
     */
    public function addUsers(User $user)
    {
        if (!$this->users->contains($user))
        {
            $this->users->add($user);
        }
    }

    public function removeUser(User $user)
    {
        $this->users->removeElement($user);
    }

    /**
     * @return mixed
     */
    public function getFormattedAddress()
    {
        return $this->formattedAddress;
    }

    /**
     * @param mixed $formattedAddress
     */
    public function setFormattedAddress($formattedAddress)
    {
        $this->formattedAddress = $formattedAddress;
    }

    /**
     * @return mixed
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * @param mixed $lon
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
    }

    /**
     * @return mixed
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param mixed $lat
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return mixed
     */
    public function getAddAt()
    {
        return $this->add_at;
    }

    /**
     * @param mixed $add_at
     */
    public function setAddAt($add_at)
    {
        $this->add_at = $add_at;
    }

    public function getParams()
    {
        return [
            "country",
            "administrative_area_level_1",
            "administrative_area_level_2",
            "locality",
            "sublocality_level_1",
            "route",
            "street_number"
        ];
    }

    public function setParam($name,$value)
    {
        if(in_array($name,$this->getParams())){
            $name = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $name))));//camelcase
            $this->$name  = $value;
        }
    }

    public function __toString()
    {
        return json_encode(["id"=>$this->getGoogleId(),"address"=>$this->getFormattedAddress()],JSON_UNESCAPED_UNICODE);
    }
}