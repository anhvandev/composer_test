<?php











namespace Composer\Repository;












class PearRepository extends ArrayRepository
{
public function __construct()
{
throw new \RuntimeException('The PEAR repository has been removed from Composer 2.0');
}
}
