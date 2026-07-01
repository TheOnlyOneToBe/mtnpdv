<?php

declare(strict_types=1);

namespace App\Application\PointVente;

final class CodeRefDejaUtiliseException extends \DomainException
{
    public function __construct(string $codeRef)
    {
        parent::__construct(sprintf('Le code de référence "%s" est déjà utilisé par un autre point de vente.', $codeRef));
    }
}
