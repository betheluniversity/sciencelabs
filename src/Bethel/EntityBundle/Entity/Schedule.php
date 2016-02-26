<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Bethel\EntityBundle\Validator as BethelAssert;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Schedule
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\ScheduleRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Schedule
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="room", type="string", length=255)
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $room;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startTime", type="time")
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="time")
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $endTime;

    /**
     * @var integer
     *
     * @ORM\Column(name="dayOfWeek", type="integer")
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $dayOfWeek;

    /**
     * @var string
     *
     * @ORM\Column(name="term", type="string", length=255)
     * @BethelAssert\Term
     */
    private $term;

    /**
     * @ORM\OneToMany(targetEntity="TutorSchedule", mappedBy="schedule", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     */
    protected $tutorSchedules;

    /**
     * @ORM\OneToMany(targetEntity="Session", mappedBy="schedule")
     * @Groups({"sessionDetails"})
     */
    protected $sessions;

    /**
     * @ORM\ManyToMany(targetEntity="CourseCode", inversedBy="schedules")
     * @ORM\JoinTable(name="ScheduleCourseCodes")
     */
    private $courseCodes;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    public function __construct() {
        $this->tutorSessions = new ArrayCollection();
        $this->courseCodes = new ArrayCollection();
    }

    public function __toString() {
        return $this->getName();
    }

    public function getTextDow() {
        $dowArray = array(
            0 => 'Sun',
            1 => 'Mon',
            2 => 'Tue',
            3 => 'Wed',
            4 => 'Thu',
            5 => 'Fri',
            6 => 'Sat'
        );
        return $dowArray[$this->dayOfWeek];
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
     * @return Schedule
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
     * Set room
     *
     * @param string $room
     * @return Schedule
     */
    public function setRoom($room)
    {
        $this->room = $room;

        return $this;
    }

    /**
     * Get room
     *
     * @return string 
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return Schedule
     */
    public function setStartTime($startTime)
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return \DateTime 
     */
    public function getStartTime()
    {
        return $this->startTime;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return Schedule
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return \DateTime 
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * Set dayOfWeek
     *
     * @param string $dayOfWeek
     * @return Schedule
     */
    public function setDayOfWeek($dayOfWeek)
    {
        $this->dayOfWeek = $dayOfWeek;

        return $this;
    }

    /**
     * Get dayOfWeek
     *
     * @return string
     */
    public function getDayOfWeek()
    {
        return $this->dayOfWeek;
    }

    /**
     * @param string $term
     * @return Schedule
     */
    public function setTerm($term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
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
     * @return Session
     */
    public function addTutorSchedule(TutorSchedule $tutorSchedule)
    {
        if (!$this->tutorSchedules->contains($tutorSchedule)) {
            $this->tutorSchedules->add($tutorSchedule);
            $tutorSchedule->setSchedule($this);
        }

        return $this;
    }

    /**
     * @param TutorSchedule $tutorSchedule
     * @return Session
     */
    public function removeTutorSchedule(TutorSchedule $tutorSchedule)
    {
        if ($this->tutorSchedules->contains($tutorSchedule)) {
            $this->tutorSchedules->removeElement($tutorSchedule);
            $tutorSchedule->setSchedule(null);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSessions()
    {
        return $this->sessions;
    }

    public function getCourseCodes()
    {
        return $this->courseCodes;
    }

    public function addCourseCode(CourseCode $courseCode)
    {
        if (!$this->courseCodes->contains($courseCode)) {
            $this->courseCodes->add($courseCode);
            // updating the inverse side
            $courseCode->addSchedule($this);
        }

        return $this;
    }

    public function removeCourseCode(CourseCode $course)
    {
        if ($this->courseCodes->contains($course)) {
            $this->courseCodes->removeElement($course);
            // updating the inverse side
            $course->removeSchedule($this);
        }

        return $this;
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
}
