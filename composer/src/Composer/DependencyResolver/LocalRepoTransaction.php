<?php











namespace Composer\DependencyResolver;

use Composer\Repository\RepositoryInterface;





class LocalRepoTransaction extends Transaction
{
public function __construct(RepositoryInterface $lockedRepository, $localRepository)
{
parent::__construct(
$localRepository->getPackages(),
$lockedRepository->getPackages()
);
}
}
