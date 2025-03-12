<?php

namespace App\Entity;

use App\Repository\ProcessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProcessRepository::class)]
class Process
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $requiredMemory = null;

    #[ORM\Column]
    private ?int $requiredCpu = null;

    #[ORM\ManyToOne(inversedBy: 'processes')]
    private ?WorkerMachine $workerMachine = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getRequiredMemory(): ?int
    {
        return $this->requiredMemory;
    }

    public function setRequiredMemory(?int $requiredMemory): static
    {
        $this->requiredMemory = $requiredMemory;

        return $this;
    }

    public function getRequiredCpu(): ?int
    {
        return $this->requiredCpu;
    }

    public function setRequiredCpu(?int $requiredCpu): static
    {
        $this->requiredCpu = $requiredCpu;

        return $this;
    }

    public function getWorkerMachine(): ?WorkerMachine
    {
        return $this->workerMachine;
    }

    public function setWorkerMachine(?WorkerMachine $workerMachine): static
    {
        $this->workerMachine = $workerMachine;

        return $this;
    }
}
