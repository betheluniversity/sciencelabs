<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TutorSchedule
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class TutorSchedule
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
     * @ORM\Column(name="schedTimeIn", type="time", nullable=false)
     */
    private $schedTimeIn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="schedTimeOut", type="time", nullable=false)
     */
    private $schedTimeOut;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tutorSchedules")
     * @ORM\JoinColumn(name="tutorId", referencedColumnName="id", nullable=false)
     */
    protected $tutor;

    /**
     * @ORM\ManyToOne(targetEntity="Schedule", inversedBy="tutorSchedules", cascade={"persist"})
     * @ORM\JoinColumn(name="scheduleId", referencedColumnName="id", nullable=false)
     */
    protected $schedule;

    /**
     * Indicates whether the tutor is the lead
     * tutor for the associated session.
     *
     * @var boolean
     * @ORM\Column(name="lead", type="boolean")
     */
    private $lead;

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
     * @param \DateTime $schedTimeIn
     *
     * @return TutorSession
     */
    public function setSchedTimeIn($schedTimeIn)
    {
        $this->schedTimeIn = $schedTimeIn;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSchedTimeIn()
    {
        return $this->schedTimeIn;
    }

    /**
     * @param \DateTime $schedTimeOut
     *
     * @return TutorSession
     */
    public function setSchedTimeOut($schedTimeOut)
    {
        $this->schedTimeOut = $schedTimeOut;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getSchedTimeOut()
    {
        return $this->schedTimeOut;
    }

    /**
     * Set tutor
     *
     * @param User $tutor
     * @return TutorSession
     */
    public function setTutor($tutor)
    {
        $this->tutor = $tutor;

        return $this;
    }

    /**
     * Get tutor
     *
     * @return User
     */
    public function getTutor()
    {
        return $this->tutor;
    }

    /**
     * Set session
     *
     * @param Schedule $schedule
     * @return TutorSession
     */
    public function setSchedule($schedule)
    {
        $this->schedule = $schedule;

        return $this;
    }

    /**
     * Get session
     *
     * @return Session
     */
    public function getSchedule()
    {
        return $this->schedule;
    }


    /**
     * @param boolean $lead
     * @return TutorSession
     */
    public function setLead($lead)
    {
        $this->lead = $lead;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getLead()
    {
        return $this->lead;
    }
}
