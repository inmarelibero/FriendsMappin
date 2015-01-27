<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TwitterAccount
 *
 * @ORM\Table("twitter_account")
 * @ORM\Entity(repositoryClass="AppBundle\Entity\TwitterAccountRepository")
 */
class TwitterAccount
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
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="json_user_serialization", type="text", nullable=true)
     */
    private $jsonUserSerialization;

    /**
     * @var datetime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var datetime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;


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
     * Set username
     *
     * @param string $username
     * @return TwitterAccount
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set jsonUserSerialization
     *
     * @param string $jsonUserSerialization
     * @return TwitterAccount
     */
    public function setJsonUserSerialization($jsonUserSerialization)
    {
        $this->jsonUserSerialization = $jsonUserSerialization;

        return $this;
    }

    /**
     * Get jsonUserSerialization
     *
     * @return string 
     */
    public function getJsonUserSerialization()
    {
        return $this->jsonUserSerialization;
    }

    /**
     * Set created
     *
     * @param \DateTime $created
     * @return TwitterAccount
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     * @return TwitterAccount
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
