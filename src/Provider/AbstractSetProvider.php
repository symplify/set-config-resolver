<?php

declare(strict_types=1);

namespace Symplify\SetConfigResolver\Provider;

use Nette\Utils\Strings;
use Symplify\EasyTesting\Exception\ShouldNotHappenException;
use Symplify\SetConfigResolver\Contract\SetProviderInterface;
use Symplify\SetConfigResolver\Exception\SetNotFoundException;
use Symplify\SetConfigResolver\ValueObject\Set;

abstract class AbstractSetProvider implements SetProviderInterface
{
    /**
     * @return string[]
     */
    public function provideSetNames(): array
    {
        $setNames = [];
        foreach ($this->provide() as $set) {
            $setNames[] = $set->getName();
        }

        return $setNames;
    }

    public function provideByName(string $desiredSetName): ?Set
    {
        // 1. name-based approach
        foreach ($this->provide() as $set) {
            if ($set->getName() !== $desiredSetName) {
                continue;
            }

            return $set;
        }

        // 2. path-based approach
        foreach ($this->provide() as $set) {
            // possible bug for PHAR files, see https://bugs.php.net/bug.php?id=52769
            // this is very tricky to handle, see https://stackoverflow.com/questions/27838025/how-to-get-a-phar-file-real-directory-within-the-phar-file-code
            $setUniqueId = $this->resolveSetUniquePathId($set->getSetFileInfo()->getPathname());
            $desiredSetUniqueId = $this->resolveSetUniquePathId($desiredSetName);

            if ($setUniqueId !== $desiredSetUniqueId) {
                continue;
            }

            return $set;
        }

        $message = sprintf('Set "%s" was not found', $desiredSetName);
        throw new SetNotFoundException($message, $desiredSetName, $this->provideSetNames());
    }

    private function resolveSetUniquePathId(string $setPath): string
    {
        $setPath = Strings::after($setPath, DIRECTORY_SEPARATOR, -2);
        if ($setPath === null) {
            throw new ShouldNotHappenException();
        }

        return $setPath;
    }
}
