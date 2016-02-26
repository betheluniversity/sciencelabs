<?php

namespace Bethel\EntityBundle\Entity;

use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\MaxDepth;

/**
 * Role
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Role implements RoleInterface
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
     * @ORM\Column(name="name", type="string", length=30)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="role", type="string", length=20, unique=true)
     */
    private $role;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="roles")
     * @ORM\JoinTable(name="UserRoles")
     * @MaxDepth(1)
     */
    private $users;

    /**
     * @var integer
     *
     * Allows us to sort roles in a more intuitive way
     *
     * @ORM\Column(name="sort", type="integer", nullable=true)
     */
    private $sort;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function __toString() {
        return $this->getName();
    }

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
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set role
     *
     * @param string $role
     * @return Role
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return string
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
    }

    public function getUsers()
    {
        return $this->users->toArray();
    }

    // The User entity is the owning side of this relationship, and thus
    // contains all of the necessary logic to keep the Role entity up to
    // date. These methods should not be called directly, but only when
    // adding a role to a user through User addRole method
    public function addUser(User $user)
    {
        $this->users->add($user);

        return $this;
    }

    public function removeUser(User $user)
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * @param int $sort
     * @return Role $this
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

}
