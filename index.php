<?php
interface TariffInterface
{
    public function getPrice();
}

abstract class AbstractTariff implements TariffInterface
{
    protected $driverRatio = 1.0;
    protected $driverCanDrive = false;
    protected $time;
    protected $distance;
    protected $add_gps = 0;
    protected $add_driver = 0;
    protected $price = 0;

    abstract protected function calculatePrice();

    protected function checkDriverAge($age)
    {
        if (($age < 18) || ($age > 65)) {
            $this->driverCanDrive = false;
        } elseif (($age >= 18) & ($age <=21)) {
            $this->driverCanDrive = true;
            $this->driverRatio = 1.1;
        } else {
            $this->driverCanDrive = true;
        }
    }

    public function getPrice()
    {
        return $this->price;
    }
}

trait AddGps
{
    protected function addGps()
    {
        $this->add_gps = 15;
    }
}

trait AddDriver
{
    protected function addDriver()
    {
        $this->add_driver = 100;
    }
}

class BaseTariff extends AbstractTariff
{
    use AddGps;

    const COST_IN_DISTANCE = 10; //стоимость рублей за км
    const COST_IN_HOUR = 180; //стоимость рублей за час (3руб./мин.)

    public function __construct($distance, $time, $age, $gps = false)
    {
        $this->checkDriverAge($age);
        if(($distance <= 0) || ($time <= 0)) {
            $this->price = 'Параметры не могут быть равны или меньше нуля';
            return -1;
        }
        $this->time = $time;
        $this->distance = $distance;
        if ($gps) {
            $this->addGps();
            $this->time = ceil($this->time);
        }
        if ($this->driverCanDrive) {
            $this->calculatePrice();
        } else {
            $this->price = 'Этот водитель не может управлять транспортным средством';
            return -1;
        }
    }

    protected function calculatePrice()
    {
        $this->price = ($this->distance * self::COST_IN_DISTANCE + $this->time * (self::COST_IN_HOUR + $this->add_gps)) * $this->driverRatio;
    }
}

class HourTariff extends AbstractTariff
{
    use AddDriver, AddGps;

    const COST_IN_DISTANCE = 0; //стоимость рублей за км
    const COST_IN_HOUR = 200; //стоимость рублей за час

    public function __construct($distance, $time, $age, $gps = false, $add_driver = false)
    {
        if(($distance <= 0) || ($time <= 0)) {
            $this->price = 'Параметры не могут быть равны или меньше нуля';
            return -1;
        }
        $this->time = ceil($time);
        $this->distance = $distance;
        if ($gps) {
            $this->addGps();
        }
        if ($add_driver) {
            $this->addDriver();
        }
        $this->checkDriverAge($age);
        if ($this->driverCanDrive) {
            $this->calculatePrice();
        } else {
            $this->price =  'Этот водитель не может управлять транспортным средством';
            return -1;
        }
    }

    protected function calculatePrice()
    {
        $this->price = ($this->distance * self::COST_IN_DISTANCE + $this->time * (self::COST_IN_HOUR + $this->add_gps)) * $this->driverRatio + $this->add_driver;
    }
}

class DayTariff extends AbstractTariff
{
    use AddDriver, AddGps;

    const COST_IN_DISTANCE = 1; //стоимость рублей за км
    const COST_IN_HOUR = 1000 / 24; //стоимость рублей за день

    public function __construct($distance, $time, $age, $gps = false, $add_driver = false)
    {
        if(($distance<=0) || ($time <=0)) {
            $this->price = 'Параметры не могут быть равны или меньше нуля';
            return -1;
        }
        $this->time = $this->getDays($time) * 24;
        $this->distance = $distance;
        if ($gps) {
            $this->addGps();
        }
        if ($add_driver) {
            $this->addDriver();
        }
        $this->checkDriverAge($age);
        if ($this->driverCanDrive) {
            $this->calculatePrice();
        } else {
            $this->price = 'Этот водитель не может управлять транспортным средством';
            return -1;
        }
    }

    protected function getDays($time)
    {
        if ($time <= 24) {
            return 1;
        } elseif ($time%24 <= 0.5) {
            return intdiv($time, 24);
        } else {
            return intdiv($time, 24) + 1;
        }
    }

    protected function calculatePrice()
    {
        $this->price = ($this->distance * self::COST_IN_DISTANCE + $this->time * (self::COST_IN_HOUR + $this->add_gps)) * $this->driverRatio + $this->add_driver;
    }
}

class StudentTariff extends AbstractTariff
{
    use AddGps;

    const COST_IN_DISTANCE = 4; //стоимость рублей за км
    const COST_IN_HOUR = 60; //стоимость рублей за час

    public function __construct($distance, $time, $age, $gps = false, $add_driver = false)
    {
        if(($distance<=0) || ($time <=0)) {
            $this->price =  'Параметры не могут быть равны или меньше нуля';
            return -1;
        }
        $this->time = $time;
        $this->distance = $distance;
        if ($gps) {
            $this->addGps();
            $this->time = ceil($this->time);
        }
        if ($age > 25) {
            $this->price =  'В данном тарифе водитель должен быть не старше 25 лет';
            return -1;
        } else {
            $this->checkDriverAge($age);
        }
        if ($this->driverCanDrive) {
            $this->calculatePrice();
        } else {
            $this->price =  'Этот водитель не может управлять транспортным средством';
            return -1;
        }
    }

    protected function calculatePrice()
    {
        $this->price = ($this->distance * self::COST_IN_DISTANCE + $this->time * (self::COST_IN_HOUR + $this->add_gps)) * $this->driverRatio;
    }
}


$t1 = new BaseTariff(10, 1.2, 24);
$t2 = new BaseTariff(20, 2.2, 24, true);
$t3 = new HourTariff(10, 1.2, 24);
$t4 = new HourTariff(30, 3.2, 21, true, true);
$t5 = new DayTariff(10, 1.2, 24);
$t6 = new DayTariff(700, 24.2, 24, true);
$t7 = new StudentTariff(10, 1.2, 24);
$t8 = new StudentTariff(50, 2.2, 20);
echo 'Базовый тариф: ' . $t1->getPrice() . '<br>' . PHP_EOL;
echo 'Базовый тариф: ' . $t2->getPrice() . '<br>' . PHP_EOL;
echo 'Часовой тариф: ' . $t3->getPrice() . '<br>' . PHP_EOL;
echo 'Часовой тариф: ' . $t4->getPrice() . '<br>' . PHP_EOL;
echo 'Суточный тариф: ' . $t5->getPrice() . '<br>' . PHP_EOL;
echo 'Суточный тариф: ' . $t6->getPrice() . '<br>' . PHP_EOL;
echo 'Студентческий тариф: ' . $t7->getPrice() . '<br>' . PHP_EOL;
echo 'Студентческий тариф: ' . $t8->getPrice() . '<br>' . PHP_EOL;
