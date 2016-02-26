<?php

namespace Bethel\EntityBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;

/**
 * TutorSession
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class TutorSession
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"tutorSchedules"})
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="schedTimeIn", type="time", nullable=TRUE)
     * @Groups({"tutorSchedules"})
     */
    private $schedTimeIn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="schedTimeOut", type="time", nullable=TRUE)
     * @Groups({"tutorSchedules"})
     */
    private $schedTimeOut;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timeIn", type="time", nullable=TRUE)
     */
    private $timeIn;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="timeOut", type="time", nullable=TRUE)
     */
    private $timeOut;

    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="tutorSessions")
     * @ORM\JoinColumn(name="tutorId", referencedColumnName="id", nullable=FALSE)
     * @Groups({"tutorSchedules"})
     */
    protected $tutor;

    /**
     * @ORM\ManyToOne(targetEntity="Session", inversedBy="tutorSessions")
     * @ORM\JoinColumn(name="sessionId", referencedColumnName="id", nullable=FALSE)
     * @Groups({"tutorSchedules"})
     */
    protected $session;

    /**
     * Indicates whether the tutor is the lead
     * tutor for the associated session.
     *
     * @var boolean
     * @ORM\Column(name="lead", type="boolean")
     */
    private $lead;

    /**
     * Indicates whether the tutor has opened this session for substitutes
     *
     * @var boolean
     * @ORM\Column(name="substitutable", type="boolean")
     * @Groups({"tutorSchedules"})
     */
    private $substitutable;

    public function __construct() {
        $this->substitutable = false;
    }

    public function getInterval() {
        // We only get a non-zero interval if the tutor has signed out
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
     * Set timeIn
     *
     * @param \DateTime $timeIn
     * @return TutorSession
     */
    public function setTimeIn($timeIn)
    {
        $this->timeIn = $timeIn;

        return $this;
    }

    /**
     * Get timeIn
     *
     * @return \DateTime 
     */
    public function getTimeIn()
    {
        return $this->timeIn;
    }

    /**
     * Set timeOut
     *
     * @param \DateTime $timeOut
     * @return TutorSession
     */
    public function setTimeOut($timeOut)
    {
        $this->timeOut = $timeOut;

        return $this;
    }

    /**
     * Get timeOut
     *
     * @return \DateTime 
     */
    public function getTimeOut()
    {
        return $this->timeOut;
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
     * @param Session $session
     * @return TutorSession
     */
    public function setSession($session)
    {
        $this->session = $session;

        return $this;
    }

    /**
     * Get session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
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

    /**
     * @param boolean $substitutable
     * @return TutorSession
     */
    public function setSubstitutable($substitutable)
    {
        $this->substitutable = $substitutable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getSubstitutable()
    {
        return $this->substitutable;
    }
}
