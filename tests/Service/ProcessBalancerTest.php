<?php
namespace App\Tests\Service;

use App\Entity\Process;
use App\Entity\WorkerMachine;
use App\Service\ProcessBalancer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProcessBalancerTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private ProcessBalancer $balancer;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->balancer = new ProcessBalancer($this->em);

        // Database cleaning
        $this->em->getConnection()->executeQuery('DELETE FROM process');
        $this->em->getConnection()->executeQuery('DELETE FROM worker_machine');
    }

    public function testInvalidProcessCreation(): void
    {
        $process = new Process();
        $process->setRequiredMemory(-100)->setRequiredCpu(-2);

        $this->expectException(\InvalidArgumentException::class);
        $this->balancer->assignProcess($process);
    }

    /** Test: Successful process assigning on machine */
    public function testAssignProcessSuccess(): void
    {
        $machine = new WorkerMachine();
        $machine->setTotalMemory(1000);
        $machine->setTotalCpu(4);
        $this->em->persist($machine);
        $this->em->flush();

        $process = new Process();
        $process->setRequiredMemory(500);
        $process->setRequiredCpu(2);

        $this->balancer->assignProcess($process);

        $this->assertSame($machine, $process->getWorkerMachine());
    }

    /** Test: Not enough resources on machines */
    public function testAssignProcessNoResources(): void
    {
        $machine = new WorkerMachine();
        $machine->setTotalMemory(100);
        $machine->setTotalCpu(1);
        $this->em->persist($machine);
        $this->em->flush();

        $process = new Process();
        $process->setRequiredMemory(200);
        $process->setRequiredCpu(1);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No available machine with enough resources.');
        $this->balancer->assignProcess($process);
    }

    /** Test: Rebalancing after machine deletion */
    public function testRebalanceAfterMachineRemoval(): void
    {
        $machine1 = (new WorkerMachine())->setTotalMemory(1000)->setTotalCpu(4);
        $machine2 = (new WorkerMachine())->setTotalMemory(2000)->setTotalCpu(8);
        $this->em->persist($machine1);
        $this->em->persist($machine2);
        $this->em->flush();

        $process = new Process();
        $process->setRequiredMemory(500)->setRequiredCpu(2);
        $this->balancer->assignProcess($process);
        $this->assertSame($machine2, $process->getWorkerMachine());

        $this->em->remove($machine1);
        $this->em->flush();

        $this->balancer->rebalance();

        $this->em->clear();
        $reloadedProcess = $this->em->getRepository(Process::class)->find($process->getId());

        $this->assertSame($machine2->getId(), $reloadedProcess->getWorkerMachine()->getId());
    }

    // self-explanatory
    public function testLoadDistributionAcrossMachines(): void
    {
        $machine1 = (new WorkerMachine())->setTotalMemory(1000)->setTotalCpu(4);
        $machine2 = (new WorkerMachine())->setTotalMemory(2000)->setTotalCpu(8);
        $machine3 = (new WorkerMachine())->setTotalMemory(3000)->setTotalCpu(12);
        $this->em->persist($machine1);
        $this->em->persist($machine2);
        $this->em->persist($machine3);
        $this->em->flush();

        for ($i = 0; $i < 6; $i++) {
            $process = new Process();
            $process->setRequiredMemory(500)->setRequiredCpu(2);
            $this->balancer->assignProcess($process);
        }

        $machine1Processes = $machine1->getProcesses()->count();
        $machine2Processes = $machine2->getProcesses()->count();
        $machine3Processes = $machine3->getProcesses()->count();

        $this->assertLessThanOrEqual(2, $machine1Processes);
        $this->assertLessThanOrEqual(3, $machine2Processes);
        $this->assertLessThanOrEqual(4, $machine3Processes);
    }

    public function testRebalanceOnNewMachineAdded(): void
    {
        $machine = (new WorkerMachine())->setTotalMemory(1000)->setTotalCpu(4);
        $this->em->persist($machine);
        $this->em->flush();

        $process = new Process();
        $process->setRequiredMemory(500)->setRequiredCpu(2);
        $this->balancer->assignProcess($process);

        // New machine with noticeably more resources
        $newMachine = (new WorkerMachine())->setTotalMemory(2000)->setTotalCpu(8);
        $this->em->persist($newMachine);
        $this->em->flush();

        $this->balancer->rebalance();

        // If we run rebalance then process should be assigned to the machine with more resouurces
        $this->em->clear();
        $reloadedProcess = $this->em->getRepository(Process::class)->find($process->getId());
        $this->assertSame($newMachine->getId(), $reloadedProcess->getWorkerMachine()->getId());
    }

    /**
     * @dataProvider processDataProvider
     */
    public function testProcessAssignment(int $memory, int $cpu, $expected)
    {
        $machine = (new WorkerMachine())->setTotalMemory(1000)->setTotalCpu(4);
        $this->em->persist($machine);
        $this->em->flush();

        $process = new Process();
        $process->setRequiredMemory($memory)->setRequiredCpu($cpu);

        if ($expected !== true) {
            $this->expectException($expected); // Ожидаем конкретный класс исключения
        }

        $this->balancer->assignProcess($process);

        if ($expected === true) {
            $this->assertNotNull($process->getWorkerMachine());
        }
    }

    public function processDataProvider(): array
    {
        return [
            'Valid Process' => [500, 2, true],
            'Too Much Memory' => [1500, 2, \RuntimeException::class],
            'Too Much CPU' => [500, 5, \RuntimeException::class],
            'Zero Resources' => [0, 0, \InvalidArgumentException::class],
        ];
    }

    public function testPerformanceUnderLoad(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $machine = (new WorkerMachine())->setTotalMemory(10000)->setTotalCpu(40);
            $this->em->persist($machine);
        }
        $this->em->flush();

        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            $process = new Process();
            $process->setRequiredMemory(500)->setRequiredCpu(2);
            $this->balancer->assignProcess($process);
        }
        $executionTime = microtime(true) - $startTime;

        $this->assertLessThan(2, $executionTime, "Execution time is too long: $executionTime seconds");
    }

}