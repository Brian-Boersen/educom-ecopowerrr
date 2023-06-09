<?php

namespace App\Repository;

use App\Entity\Devices;
use App\Entity\MothlyYield;

use App\Repository\QuarterYieldRepository;

use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use PhpOffice\PhpSpreadsheet\Calculation\DateTimeExcel\Date;

/**
 * @extends ServiceEntityRepository<MothlyYield>
 *
 * @method MothlyYield|null find($id, $lockMode = null, $lockVersion = null)
 * @method MothlyYield|null findOneBy(array $criteria, array $orderBy = null)
 * @method MothlyYield[]    findAll()
 * @method MothlyYield[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MothlyYieldRepository extends ServiceEntityRepository
{
    private $devicesMonthlyYield = null;

    public function __construct(ManagerRegistry $registry, private QuarterYieldRepository $quarterYieldRepository)
    {
        parent::__construct($registry, MothlyYield::class);
    }

    public function fetchByDevice(Devices $device): array
    {
        return $this->findBy(['device' => $device]);
    }

    public function save(array $data,Devices $dev, bool $flush = true , bool $first = true)
    {
        $splitDate = explode("/", $data['date']);

        $deviceDate = new \DateTime($splitDate[2].'-'.$splitDate[1].'-'.$splitDate[0]);
        
        $deviceDays =  $deviceDate->format('j');
        $deviceDate->modify('-'.($deviceDays - 1).' days');
        
        $deviceEndDate = new \DateTime($splitDate[2].'-'.$splitDate[1].'-'.$splitDate[0]);
        $deviceEndDate->modify('+1 month');

        $thisYear = new \DateTime();
        $thisYear->modify('-1 year');

        if($first == true)
        {
            print_r("\n first device id: " . $dev->getId() . "\n");

            $this->devicesMonthlyYield = [];

            // $panels = $this->findBy(['device' => $dev]);

            // foreach($panels as $panel)
            // {
            //     $this->devicesMonthlyYield = [$panel->getSerialNumber(), $panel->getStartDate()];
            // }
        }
        
        
        $skip = false;
        
        foreach($data['devices'] as $device)
        {
            
            if($deviceDate->format('ym') <= $thisYear->format('ym'))
            {
                $this->quarterYieldRepository->save($dev, $device, $deviceDate, $thisYear, $flush, $first);
                continue;
            }
            
            $keys = array_keys($this->devicesMonthlyYield, $device['serial_number']);

            if($first == false || $this->devicesMonthlyYield == null)
            {
                $this->devicesMonthlyYield[] = [$device['serial_number'], $deviceDate];
            }
            
            foreach($keys as $key)
            {
                if((int)$this->devicesMonthlyYield[$key][1] == (int)$deviceDate->format('ymd'))
                {
                    $skip = true;
                    break;
                }
            } 
            
            if($skip === true)
            {
                $skip = false;
                continue;
            }
            // foreach($devicesMonthlyYield as $deviceMonthlyYield)
            // {
            //     if((int)$deviceMonthlyYield->getStartDate()->format('ymd') == (int)$deviceDate->format('ymd'))
            //     {
            //         $skip = true;
            //         break;
            //     }
            // }

            $entity = $this->makeEntety($dev,$device,$deviceDate,$deviceEndDate);

            $this->getEntityManager()->persist($entity);

            if ($flush) 
            {
                $this->getEntityManager()->flush();
            }
        }
    }

    private function makeEntety($dev,$device,$deviceDate,$deviceEndDate): MothlyYield
    {
        $entity = new MothlyYield();

        $entity->setDevice($dev);
        $entity->setSerialNumber(($device['serial_number']));

        $entity->setYield(floatval($device['device_month_yield']));
        $entity->setSurplus(floatval($device['device_month_surplus']));
        
        $entity->setStartDate($deviceDate);
        $entity->setEndDate($deviceEndDate);

        //$this->getEntityManager()->persist($entity);
        return $entity;
    }

    public function remove(MothlyYield $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MothlyYield[] Returns an array of MothlyYield objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MothlyYield
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
