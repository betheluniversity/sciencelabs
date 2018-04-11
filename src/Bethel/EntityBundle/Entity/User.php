<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\EntityListeners({"Bethel\EntityBundle\EventListener\UserListener"})
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\UserRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class User implements UserInterface, \Serializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"sessionDetails"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255)
     * @Groups({"userInfo", "sessionDetails"})
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $password;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     * @Groups({"sessionDetails"})
     *
     */
    private $roles;

    /**
     * @ORM\OneToMany(targetEntity="StudentSession", mappedBy="student", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     *
     */
    protected $studentSessions;

    /**
     * @ORM\OneToMany(targetEntity="TutorSession", mappedBy="tutor", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     *
     */
    protected $tutorSessions;

    /**
     * @ORM\OneToMany(targetEntity="TutorSchedule", mappedBy="tutor", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     *
     */
    protected $tutorSchedules;

    /**
     * @var string
     *
     * @ORM\Column(name="firstName", type="string", length=255, nullable=true)
     * @Assert\NotBlank
     * @Groups({"userInfo", "sessionDetails", "tutorSchedules"})
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(name="lastName", type="string", length=255, nullable=true)
     * @Groups({"userInfo", "sessionDetails", "tutorSchedules"})
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var boolean
     *
     * @ORM\Column(name="send_email", type="boolean")
     */
    private $sendEmail;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @ORM\ManyToMany(targetEntity="Course", inversedBy="students")
     */
    private $courses;

    /**
     * @ORM\ManyToMany(targetEntity="Course", mappedBy="professors")
     */
    protected $professorCourses;

//    /**
//     * @ORM\ManyToMany(targetEntity="Course", inversedBy="courseViewer")
//     * @ORM\JoinTable(
//     *  name="CourseViewer",
//     *  joinColumns={
//     *      @ORM\JoinColumn(name="user_id", referencedColumnName="id")
//     *  },
//     *  inverseJoinColumns={
//     *      @ORM\JoinColumn(name="course_id", referencedColumnName="id")
//     *  }
//     * )
//     */

    /**
     * @ORM\ManyToMany(targetEntity="Course", inversedBy="user")
     * @ORM\JoinTable(name="courseviewer", )
     */
    private $courseViewers;


    public function __construct()
    {
        $this->studentSessions = new ArrayCollection();
        $this->tutorSessions = new ArrayCollection();
        $this->tutorSchedules = new ArrayCollection();
        $this->roles = new ArrayCollection();
        $this->courses = new ArrayCollection();
        $this->professorCourses = new ArrayCollection();
        $this->isActive = true;
        $this->sendEmail = false;
        $this->courseViewers = new ArrayCollection();
    }

    public function __toString() {
        return $this->firstName . " " . $this->lastName;
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
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    // If you want to make sure that your collections are perfectly encapsulated you should not
    // return them from a getCollectionName() method directly, but call $collection->toArray().
    // http://docs.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-associations.html#association-management-methods

    /**
     * @return array
     */
    public function getStudentSessions()
    {
        return $this->studentSessions->toArray();
    }

    /**
     * @param StudentSession $studentSession
     * @return User
     */
    public function addStudentSession(StudentSession $studentSession)
    {
        if (!$this->studentSessions->contains($studentSession)) {
            $this->studentSessions->add($studentSession);
            $studentSession->setStudent($this);
        }

        return $this;
    }

    /**
     * @param StudentSession $studentSession
     * @return User
     */
    public function removeStudentSession(StudentSession $studentSession)
    {
        if ($this->studentSessions->contains($studentSession)) {
            $this->studentSessions->removeElement($studentSession);
            $studentSession->setStudent(null);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getTutorSessions()
    {
        return $this->tutorSessions->toArray();
    }

    /**
     * @param TutorSession $tutorSession
     * @return User
     */
    public function addTutorSession(TutorSession $tutorSession)
    {
        if (!$this->tutorSessions->contains($tutorSession)) {
            $this->tutorSessions->add($tutorSession);
            $tutorSession->setTutor($this);
        }

        return $this;
    }

    /**
     * @param TutorSession $tutorSession
     * @return User
     */
    public function removeTutorSession(TutorSession $tutorSession)
    {
        if ($this->tutorSessions->contains($tutorSession)) {
            $this->tutorSessions->removeElement($tutorSession);
            $tutorSession->setTutor(null);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getTutorSchedules()
    {
        return $this->tutorSchedules->toArray();
    }

    /**
     * @param TutorSchedule $tutorSchedule
     * @return User
     */
    public function addTutorSchedule(TutorSchedule $tutorSchedule)
    {
        if (!$this->tutorSchedules->contains($tutorSchedule)) {
            $this->tutorSchedules->add($tutorSchedule);
            $tutorSchedule->setTutor($this);
        }

        return $this;
    }

    /**
     * @param TutorSchedule $tutorSchedule
     * @return User
     */
    public function removeTutorSchedule(TutorSchedule $tutorSchedule)
    {
        if ($this->tutorSchedules->contains($tutorSchedule)) {
            $this->tutorSchedules->removeElement($tutorSchedule);
            $tutorSchedule->setTutor(null);
        }

        return $this;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     * @return User
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * Get firstName
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     * @return User
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Get lastName
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param boolean $sendEmail
     * @return User
     */
    public function setSendEmail($sendEmail)
    {
        $this->sendEmail = $sendEmail;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSendEmail()
    {
        return $this->sendEmail;
    }

    // UserInterface related methods

    // The only requirement is that the class implements UserInterface.
    // The methods in this interface should therefore be defined in the custom user class:
    // getRoles(), getPassword(), getSalt(), getUsername(), eraseCredentials()
    // http://symfony.com/doc/current/cookbook/security/entity_provider.html

    public function getRoles()
    {
        return $this->roles->toArray();
    }

    public function addRole(Role $role)
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
            // updating the inverse side
            $role->addUser($this);
        }

        return $this;
    }

    /**
     * Never use this to check if this user has access to anything!
     *
     * Use the SecurityContext, or an implementation of AccessDecisionManager
     * instead, e.g.
     *
     *         $securityContext->isGranted('ROLE_USER');
     *
     * @param string $role
     *
     * @return boolean
     */
    public function hasRole($role)
    {
        $userRoles = $this->getRoles();
        /** @var $userRole \Bethel\EntityBundle\Entity\Role */
        foreach($userRoles as $userRole) {
            if($userRole->getRole() == $role) {
                return true;
            }
        }

        return false;
    }

    public function removeRole(Role $role)
    {
        if ($this->roles->contains($role)) {
            $this->roles->removeElement($role);
            // updating the inverse side
            $role->removeUser($this);
        }

        return $this;
    }

    public function getPassword() {
        return $this->password;
    }

    public function getSalt()
    {
        // We should not need a salt while using CAS as a provider
        return null;
    }

    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            // $this->salt
            ) = unserialize($serialized);
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
        return $this;
    }

    public function getCourses()
    {
        return $this->courses;
    }

    public function addCourse(Course $course)
    {
        if (!$this->courses->contains($course)) {
            $this->courses->add($course);
            // updating the inverse side
            $course->addStudent($this);
        }

        return $this;
    }

    public function removeCourse(Course $course)
    {
        if ($this->courses->contains($course)) {
            $this->courses->removeElement($course);
            // updating the inverse side
            $course->removeStudent($this);
        }

        return $this;
    }

    public function getProfessorCourses()
    {
        return $this->professorCourses;
    }

    public function addProfessorCourse(Course $course)
    {
        if (!$this->professorCourses->contains($course)) {
            $this->professorCourses->add($course);
            // updating the inverse side
            $course->addProfessor($this);
        }

        return $this;
    }

    public function removeProfessorCourse(Course $course)
    {
        if ($this->professorCourses->contains($course)) {
            $this->professorCourses->removeElement($course);
            // updating the inverse side
            $course->removeProfessor($this);
        }

        return $this;
    }

    public function getCourseViewers() {
        return $this->courseViewers;
    }

    public function getCourseViewersAsArray() {
        $courseViewers = array();
        foreach( $this->courseViewers as $course)
            array_push($courseViewers, $course);
        return $courseViewers;
    }

    public function removeAllCourseViewers() {
        return $this->courseViewers->clear();
    }
}
