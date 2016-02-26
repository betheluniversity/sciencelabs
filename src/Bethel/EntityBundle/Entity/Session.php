<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Session
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\SessionRepository")
 * @Gedmo\SoftDeleteable(fieldName="deletedAt")
 */
class Session
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Semester")
     * @ORM\JoinColumn(name="semester_id", referencedColumnName="id")
     * @Groups({"sessionDetails"})
     */
    private $semester;

    /**
     * @ORM\ManyToOne(targetEntity="Schedule", inversedBy="sessions", cascade={"persist"})
     * @ORM\JoinColumn(name="schedule_id", referencedColumnName="id")
     * @Groups({"sessionDetails"})
     */
    private $schedule;

    /**
     * @ORM\OneToMany(targetEntity="StudentSession", mappedBy="session", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     * @Groups({"sessionDetails"})
     */
    protected $studentSessions;

    /**
     * @ORM\OneToMany(targetEntity="TutorSession", mappedBy="session", cascade={"persist", "remove"}, orphanRemoval=TRUE)
     * @Groups({"sessionDetails"})
     */
    protected $tutorSessions;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     * @Groups({"sessionList", "sessionDetails", "tutorSchedules"})
     */
    private $date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="schedStartTime", type="time", nullable=true)
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $schedStartTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="schedEndTime", type="time", nullable=true)
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $schedEndTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="startTime", type="time", nullable=true)
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $startTime;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="endTime", type="time", nullable=true)
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $endTime;

    /**
     * @var string
     *
     * @ORM\Column(name="room", type="string", length=255, nullable=true)
     * @Groups({"scheduleList", "scheduleDetails"})
     */
    private $room;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="openerId", referencedColumnName="id")
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $opener;

    /**
     * @var boolean
     *
     * @ORM\Column(name="open", type="boolean")
     * @Groups({"sessionList", "sessionDetails"})
     */
    private $open;

    /**
     * @ORM\ManyToMany(targetEntity="CourseCode", inversedBy="sessions")
     * @ORM\JoinTable(name="SessionCourseCodes")
     */
    private $courseCodes;

    /**
     * @var string
     *
     * @ORM\Column(name="hash", type="string", length=13)
     */
    private $hash;

    /**
     * @var string
     *
     * @ORM\Column(name="comments", type="text", nullable=true)
     */
    private $comments;

    /**
     * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
     */
    private $deletedAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="anonStudents", type="integer")
     * @Groups({"sessionDetails"})
     */
    private $anonStudents;

    public function __construct() {
        // Make it hard to guess the URL of the session
        $this->hash = uniqid();
        $this->open = false;
        $this->studentSessions = new ArrayCollection();
        $this->tutorSessions = new ArrayCollection();
        $this->courseCodes = new ArrayCollection();
        $this->anonStudents = 0;
    }

    public function __toString() {
        if($this->getName()) {
            return $this->getName() . ' (' . $this->getDate()->format('n/j/Y') . ')';
        } else {
            return $this->getDate()->format('n/j/Y');
        }
    }

    public function getStartDateTime() {
        $startDate = $this->getDate()->format('Y-m-d');
        if( $this->getStartTime() )
            $startTime = $this->getStartTime()->format('H:i:s');
        else
            $startTime = Null;
        return new \DateTime($startDate . ' ' . $startTime);
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
     * @param \Bethel\EntityBundle\Entity\Semester $semester
     * @return Session
     */
    public function setSemester($semester)
    {
        $this->semester = $semester;
        return $this;
    }

    /**
     * @return \Bethel\EntityBundle\Entity\Semester
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * @param \Bethel\EntityBundle\Entity\Schedule $schedule
     * @return Session
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;
        return $this;
    }

    /**
     * @return \Bethel\EntityBundle\Entity\Schedule
     */
    public function getSchedule()
    {
        return $this->schedule;
    }

    /**
     * @return array
     */
    public function getStudentSessions()
    {
        return $this->studentSessions->toArray();
    }

    /**
     * @param StudentSession $studentSession
     * @return Session
     */
    public function addStudentSession(StudentSession $studentSession)
    {
        if (!$this->studentSessions->contains($studentSession)) {
            $this->studentSessions->add($studentSession);
            $studentSession->setSession($this);
        }

        return $this;
    }

    /**
     * @param StudentSession $studentSession
     * @return Session
     */
    public function removeStudentSession(StudentSession $studentSession)
    {
        if ($this->studentSessions->contains($studentSession)) {
            $this->studentSessions->removeElement($studentSession);
            $studentSession->setSession(null);
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
     * @return Session
     */
    public function addTutorSession(TutorSession $tutorSession)
    {
        if (!$this->tutorSessions->contains($tutorSession)) {
            $this->tutorSessions->add($tutorSession);
            $tutorSession->setSession($this);
        }

        return $this;
    }

    /**
     * @param TutorSession $tutorSession
     * @return Session
     */
    public function removeTutorSession(TutorSession $tutorSession)
    {
        if ($this->tutorSessions->contains($tutorSession)) {
            $this->tutorSessions->removeElement($tutorSession);
            $tutorSession->setSession(null);
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getStudents()
    {
        return array_map(
            function (StudentSession $studentSession) {
                return $studentSession->getStudent();
            },
            $this->studentSessions
        );
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Session
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime 
     */
    public function getDate()
    {
        return $this->date;
    }


    /**
     * @param \DateTime $schedStartTime
     * @return Session
     */
    public function setSchedStartTime($schedStartTime)
    {
        $this->schedStartTime = $schedStartTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSchedStartTime()
    {
        return $this->schedStartTime;
    }

    /**
     * @param \DateTime $schedEndTime
     * @return Session
     */
    public function setSchedEndTime($schedEndTime)
    {
        $this->schedEndTime = $schedEndTime;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSchedEndTime()
    {
        return $this->schedEndTime;
    }

    /**
     * Set startTime
     *
     * @param \DateTime $startTime
     * @return Session
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
     * @return Session
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
     * @param string $room
     * @return Session
     */
    public function setRoom($room)
    {
        $this->room = $room;
        return $this;
    }

    /**
     * @return string
     */
    public function getRoom()
    {
        return $this->room;
    }

    /**
     * Set open
     *
     * @param boolean $open
     * @return Session
     */
    public function setOpen($open)
    {
        $this->open = $open;

        return $this;
    }

    /**
     * Get open
     *
     * @return boolean 
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * @param User $opener
     * @return Session
     */
    public function setOpener($opener)
    {
        $this->opener = $opener;

        return $this;
    }

    /**
     * @return User
     */
    public function getOpener()
    {
        return $this->opener;
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
            $courseCode->addSession($this);
        }

        return $this;
    }

    public function removeCourseCode(CourseCode $course)
    {
        if ($this->courseCodes->contains($course)) {
            $this->courseCodes->removeElement($course);
            // updating the inverse side
            $course->removeSession($this);
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @return Session
     * @param string $comments
     */
    public function setComments($comments)
    {
        $this->comments = $comments;
        return $this;
    }

    /**
     * @return string
     */
    public function getComments()
    {
        return $this->comments;
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

    /**
     * @return int
     */
    public function getAnonStudents()
    {
        return $this->anonStudents;
    }

    /**
     * @param int $anonStudents
     * @return Session $this
     */
    public function setAnonStudents($anonStudents)
    {
        $this->anonStudents = $anonStudents;
        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return Session $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }
}
