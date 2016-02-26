<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Groups;

// Reference: http://future500.nl/doctrine-2-how-to-handle-join-tables-with-extra-columns/

/**
 * StudentSession
 *
 * @ORM\Table(name="StudentSession")
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\StudentSessionRepository")
 */
class StudentSession
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="studentSessions")
     * @ORM\JoinColumn(name="studentId", referencedColumnName="id", nullable=FALSE)
     * @Groups({"sessionDetails"})
     */
    protected $student;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="studentSessions")
     * @ORM\JoinColumn(name="sessionId", referencedColumnName="id", nullable=FALSE)
     * @Groups({"sessionDetails"})
     */
    protected $session;

    /**
     * @ORM\ManyToMany(targetEntity="Course", inversedBy="studentSessions")
     * @ORM\JoinTable(name="SessionCourses")
     * @Groups({"sessionDetails"})
     */
    private $courses;

    /**
     * @var bool
     * @ORM\Column(name="otherCourse", type="boolean", nullable=true)
     * @Assert\NotBlank(message="You must select a course or 'Other'", groups={"courseOrOther"})
     */
    private $otherCourse;

    /**
     * @var string
     *
     * @ORM\Column(name="otherCourseName", type="string", nullable=true)
     * @Assert\NotBlank(message="You must select a course (or 'Other')", groups={"courseOrOther"})
     */
    private $otherCourseName;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timeIn", type="time", nullable=true)
     * @Groups({"sessionDetails"})
     */
    private $timeIn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timeOut", type="time", nullable=true)
     * @Groups({"sessionDetails"})
     */
    private $timeOut;

    public function __construct() {
        $this->courses = new ArrayCollection();
    }

    public function getInterval() {
        // We only get a non-zero interval if the student has signed out
        // (as well as in). Not signing out shows no time spent in reports.
        if($this->timeOut && $this->timeIn) {
            return $this->timeOut->diff($this->timeIn);
        } else {
            // Get a diff of 0
            $now = new \DateTime();
            return $now->diff($now);
        }
    }

    public function getMinutes() {
        $diff = $this->getInterval();
        $minutes = 0;

        $minutes += ((int) $diff->format('%h')) * 60;
        $minutes += (int) $diff->format('%i');
        return $minutes;
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
     * @param Session $session
     * @return StudentSession
     */
    public function setSession(Session $session = null)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * @param User $student
     * @return StudentSession
     */
    public function setStudent(User $student = null)
    {
        $this->student = $student;
        return $this;
    }

    /**
     * @return User
     */
    public function getStudent()
    {
        return $this->student;
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
            $course->addStudentSession($this);
        }

        return $this;
    }

    public function removeCourse(Course $course)
    {
        if ($this->courses->contains($course)) {
            $this->courses->removeElement($course);
            // updating the inverse side
            $course->removeStudentSession($this);
        }

        return $this;
    }

    /**
     * @param \DateTime $timeIn
     * @return StudentSession
     */
    public function setTimeIn($timeIn)
    {
        $this->timeIn = $timeIn;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimeIn()
    {
        return $this->timeIn;
    }

    /**
     * @param \DateTime $timeOut
     *
     * @return StudentSession
     */
    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getTimeOut()
    {
        return $this->timeOut;
    }

    /**
     * @return boolean
     */
    public function getOtherCourse()
    {
        return $this->otherCourse;
    }

    /**
     * @param boolean $otherCourse
     * @return $this
     */
    public function setOtherCourse($otherCourse)
    {
        $this->otherCourse = $otherCourse;

        return $this;
    }

    /**
     * @return string
     */
    public function getOtherCourseName()
    {
        return $this->otherCourseName;
    }

    /**
     * @param string $otherCourseName
     * @return $this
     */
    public function setOtherCourseName($otherCourseName)
    {
        $this->otherCourseName = $otherCourseName;

        return $this;
    }

}
