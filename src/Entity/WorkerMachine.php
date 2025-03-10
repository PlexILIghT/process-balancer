<?php

namespace App\Entity;

use App\Repository\WorkerMachineRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkerMachineRepository::class)]
class WorkerMachine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $totalMemory = null;

    #[ORM\Column]
    private ?int $totalCpu = null;

    /**
     * @var Collection<int, Process>
     */
    #[ORM\OneToMany(targetEntity: Process::class, mappedBy: 'workerMachine')]
    private Collection $processes;

    public function __construct()
    {
        $this->processes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getTotalMemory(): ?int
    {
        return $this->totalMemory;
    }

    public function setTotalMemory(int $totalMemory): static
    {
        $this->totalMemory = $totalMemory;

        return $this;
    }

    public function getTotalCpu(): ?int
    {
        return $this->totalCpu;
    }

    public function setTotalCpu(int $totalCpu): static
    {
        $this->totalCpu = $totalCpu;

        return $this;
    }

    /**
     * @return Collection<int, Process>
     */
    public function getProcesses(): Collection
    {
        return $this->processes;
    }

    public function addProcess(Process $process): static
    {
        if (!$this->processes->contains($process)) {
            $this->processes->add($process);
            $process->setWorkerMachine($this);
        }

        return $this;
    }

    public function removeProcess(Process $process): static
    {
        if ($this->processes->removeElement($process)) {
            // set the owning side to null (unless already changed)
            if ($process->getWorkerMachine() === $this) {
                $process->setWorkerMachine(null);
            }
        }

        return $this;
    }
}
