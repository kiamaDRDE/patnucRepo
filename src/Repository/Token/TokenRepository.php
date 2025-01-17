<?php

// src/Repository/Token/TokenRepository.php

namespace App\Repository\Token;

use App\Entity\Token\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    // Vous pouvez ajouter des méthodes personnalisées ici si nécessaire.
}
