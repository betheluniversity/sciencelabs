<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Course
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="Bethel\EntityBundle\Entity\CourseRepository")
 */
class Course
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
     * @var \DateTime
     *
     * @ORM\Column(name="begin_date", type="datetime")
     */
    private $beginDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="begin_time", type="time", nullable=true)
     */
    private $beginTime;

    /**
     * @var string
     *
     * @ORM\Column(name="course_num", type="string", length=10)
     */
    private $courseNum;

    /**
     * @var integer
     *
     * @ORM\Column(name="section", type="integer")
     */
    private $section;

    /**
     * @var integer
     *
     * @ORM\Column(name="crn", type="integer")
     */
    private $crn;

    /**
     * @var string
     *
     * @ORM\Column(name="dept", type="string", length=10)
     */
    private $dept;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_date", type="datetime")
     */
    private $endDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_time", type="time", nullable=true)
     */
    private $endTime;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="professor_id", referencedColumnName="id")
     */
    private $professor;

    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="professorCourses")
     * @ORM\JoinTable(
     *  name="CourseProfessors",
     *  joinColumns={
     *      @ORM\JoinColumn(name="course_id", referencedColumnName="id")
     *  },
     *  inverseJoinColumns={
     *      @ORM\JoinColumn(name="professor_id", referencedColumnName="id")
     *  }
     * )
     */
    private $professors;

    /**
     * @var string
     *
     * @ORM\Column(name="meeting_day", type="string", length=10)
     */
    private $meetingDay;

    /**
     * @ORM\ManyToOne(targetEntity="Semester")
     * @ORM\JoinColumn(name="semester_id", referencedColumnName="id")
     */
    private $semester;

    /**
     * @ORM\ManyToOne(targetEntity="CourseCode", inversedBy="courses")
     * @ORM\JoinColumn(name="course_code_id", referencedColumnName="id")
     */
    private $courseCode;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title;

    /**
     * @ORM\ManyToMany(targetEntity="StudentSession", mappedBy="courses")
     */
    private $studentSessions;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="courses")
     */
    private $students;

    /**
     * @var integer
     *
     * @ORM\Column(name="num_attendees", type="integer", nullable=true)
     */
    private $numAttendees;

    /**
     * @var string
     *
     * @ORM\Column(name="room", type="string", nullable=true)
     */
    private $room;


    public function __construct() {
        $this->studentSessions = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->professors = new ArrayCollection();
    }

    public function __toString() {
        if ($this->getSection()) {
            return $this->getTitle() . ' (Section ' . $this->getSection() . ')';
        }
        return $this->getTitle();
    }

    /**
     * @return array
     */
    public function getProfessorNames() {
        $names = array();
        /** @var \Bethel\EntityBundle\Entity\User $professor */
        foreach($this->getProfessors() as $professor) {
            $names[] = $professor->__toString();
        }

        return $names;
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
     * Set beginDate
     *
     * @param \DateTime $beginDate
     * @return Course
     */
    public function setBeginDate($beginDate)
    {
        $this->beginDate = $beginDate;

        return $this;
    }

    /**
     * Get beginDate
     *
     * @return \DateTime
     */
    public function getBeginDate()
    {
        return $this->beginDate;
    }

    /**
     * Set beginTime
     *
     * @param \DateTime $beginTime
     * @return Course
     */
    public function setBeginTime($beginTime)
    {
        $this->beginTime = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return \DateTime 
     */
    public function getBeginTime()
    {
        return $this->beginTime;
    }

    /**
     * Set courseNum
     *
     * @param string $courseNum
     * @return Course
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
     * @param int $section
     *
     * @return Course
     */
    public function setSection($section)
    {
        $this->section = $section;
        return $this;
    }

    /**
     * @return int
     */
    public function getSection()
    {
        return $this->section;
    }

    /**
     * Set crn
     *
     * @param integer $crn
     * @return Course
     */
    public function setCrn($crn)
    {
        $this->crn = $crn;

        return $this;
    }

    /**
     * Get crn
     *
     * @return integer 
     */
    public function getCrn()
    {
        return $this->crn;
    }

    /**
     * Set dept
     *
     * @param string $dept
     * @return Course
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
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return Course
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set endTime
     *
     * @param \DateTime $endTime
     * @return Course
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
     * @return Course
     * @param User $professor
     */
    public function setProfessor($professor)
    {
        $this->professor = $professor;

        return $this;
    }

    /**
     * @return User
     */
    public function getProfessor()
    {
        return $this->professor;
    }

    /**
     * Set meetingDay
     *
     * @param string $meetingDay
     * @return Course
     */
    public function setMeetingDay($meetingDay)
    {
        $this->meetingDay = $meetingDay;

        return $this;
    }

    /**
     * Get meetingDay
     *
     * @return string 
     */
    public function getMeetingDay()
    {
        return $this->meetingDay;
    }

    /**
     * @param Semester $semester
     *
     * @return Course
     */
    public function setSemester($semester)
    {
        $this->semester = $semester;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getSemester()
    {
        return $this->semester;
    }

    /**
     * @return mixed
     */
    public function getCourseCode()
    {
        return $this->courseCode;
    }

    /**
     * @param mixed $courseCode
     * @return Course
     */
    public function setCourseCode($courseCode)
    {
        $this->courseCode = $courseCode;

        return $this;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Course
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    public function getStudentSessions()
    {
        return $this->studentSessions->toArray();
    }

    public function addStudentSession(StudentSession $studentSession)
    {
        $this->studentSessions->add($studentSession);

        return $this;
    }

    public function removeStudentSession(StudentSession $studentSession)
    {
        $this->studentSessions->removeElement($studentSession);

        return $this;
    }

    public function getStudent()
    {
        return $this->students->toArray();
    }

    public function addStudent(User $student)
    {
        $this->students->add($student);

        return $this;
    }

    public function removeStudent(User $student)
    {
        $this->students->removeElement($student);

        return $this;
    }

    public function getProfessors()
    {
        return $this->professors;
    }

    public function addProfessor(User $professor)
    {
        if (!$this->professors->contains($professor)) {
            $this->professors->add($professor);
            // updating the inverse side
            $professor->removeProfessorCourse($this);
        }

        return $this;
    }

    public function removeProfessor(User $professor)
    {
        if ($this->professors->contains($professor)) {
            $this->professors->removeElement($professor);
            // updating the inverse side
            $professor->removeProfessorCourse($this);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getNumAttendees()
    {
        return $this->numAttendees;
    }

    /**
     * @param mixed $numAttendees
     * @return $this
     */
    public function setNumAttendees($numAttendees)
    {
        $this->numAttendees = $numAttendees;

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
     * @param string $room
     * @return $this
     */
    public function setRoom($room)
    {
        $this->room = $room;

        return $this;
    }
}
