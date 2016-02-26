<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * CourseCode
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\CourseCodeRepository")
 */
class CourseCode
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
     * @ORM\Column(name="dept", type="string", length=255)
     */
    private $dept;

    /**
     * @var string
     *
     * @ORM\Column(name="courseNum", type="string", length=255)
     */
    private $courseNum;

    /**
     * @var string
     *
     * @ORM\Column(name="courseName", type="string", length=255, nullable=true)
     */
    private $courseName;

    /**
     * @var string
     *
     * @ORM\Column(name="underived", type="string", length=255)
     */
    private $underived;

    /**
     * @var boolean
     *
     * @ORM\Column(name="active", type="boolean")
     */
    private $active;

    /**
     * @ORM\ManyToMany(targetEntity="Session", mappedBy="courseCodes")
     */
    private $sessions;

    /**
     * @ORM\ManyToMany(targetEntity="Schedule", mappedBy="courseCodes")
     */
    private $schedules;

    /**
     * @ORM\OneToMany(targetEntity="Course", mappedBy="courseCode")
     */
    private $courses;

    public function __construct() {
        $this->active = true;
        $this->sessions = new ArrayCollection();
        $this->schedules = new ArrayCollection();
        $this->courses = new ArrayCollection();
    }

    public function __toString() {
        $ccString = $this->dept . $this->courseNum;
        // Since our coursecode form listing relies on commas to separate coursecodes
        // we need to make sure that there are none in the course name
        $escapedCourseName = str_replace(',', ' ',$this->courseName);
        $this->courseName ? $ccString .= ' (' . $escapedCourseName . ')' : $ccString .= '';
        return $ccString;
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
     * Set dept
     *
     * @param string $dept
     * @return CourseCode
     */
    public function setDept($dept)
    {
        $this->dept = $dept;

        return $this;
    }

    /**
     * Get dept
     *
     * @return string 
     */
    public function getDept()
    {
        return $this->dept;
    }

    /**
     * Set courseNum
     *
     * @param string $courseNum
     * @return CourseCode
     */
    public function setCourseNum($courseNum)
    {
        $this->courseNum = $courseNum;

        return $this;
    }

    /**
     * Get courseNum
     *
     * @return string 
     */
    public function getCourseNum()
    {
        return $this->courseNum;
    }

    /**
     * Set underived
     *
     * @param string $underived
     * @return CourseCode
     */
    public function setUnderived($underived)
    {
        $this->underived = $underived;

        return $this;
    }

    /**
     * Get underived
     *
     * @return string 
     */
    public function getUnderived()
    {
        return $this->underived;
    }

    /**
     * @return string
     */
    public function getCourseName()
    {
        return $this->courseName;
    }

    /**
     * @param string $courseName
     * @return CourseCode
     */
    public function setCourseName($courseName)
    {
        $this->courseName = $courseName;

        return $this;
    }

    /**
     * @param boolean $active
     *
     * @return CourseCode
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getActive()
    {
        return $this->active;
    }

    public function getSessions()
    {
        return $this->sessions->toArray();
    }

    public function addSession(Session $session)
    {
        $this->sessions->add($session);

        return $this;
    }

    public function removeSession(Session $session)
    {
        $this->sessions->removeElement($session);

        return $this;
    }

    public function getSchedules()
    {
        return $this->sessions->toArray();
    }

    public function addSchedule(Schedule $schedule)
    {
        $this->schedules->add($schedule);

        return $this;
    }

    public function removeSchedule(Schedule $schedule)
    {
        $this->schedules->removeElement($schedule);

        return $this;
    }
}
