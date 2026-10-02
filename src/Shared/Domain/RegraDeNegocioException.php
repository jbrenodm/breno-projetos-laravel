<?php

declare(strict_types=1);

namespace Src\Shared\Domain;

use DomainException;

/**
 * Base de todas as violações de regra de negócio. A mensagem é segura para exibir ao usuário.
 */
class RegraDeNegocioException extends DomainException {}
