<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\Character;
use App\Entity\Episode;
use App\Entity\Location;
use Doctrine\ORM\EntityManagerInterface;

class MeeseeksDatabaseService
{
    // supported seekTypes
    public const SEEK_EPISODE = Episode::class;
    public const SEEK_LOCATION = Location::class;
    public const SEEK_CHARACTER = Character::class;

    // supported findBy options per seekType
    public const SEEK_VALUE_ALL_NAME = 'name';
    public const SEEK_VALUE_ALL_ID = 'id';

    public const SEEK_VALUE_EPISODE_CODE = 'episodeString';
    public const SEEK_VALUE_LOCATION_DIMENSION = 'dimension';

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function supports($seekType, string $findBy): bool
    {
        $supportedTypes = [self::SEEK_EPISODE, self::SEEK_LOCATION, self::SEEK_CHARACTER];
        $supportedSeeks = [
            self::SEEK_EPISODE => [self::SEEK_VALUE_ALL_ID, self::SEEK_VALUE_ALL_NAME, self::SEEK_VALUE_EPISODE_CODE],
            self::SEEK_LOCATION => [self::SEEK_VALUE_ALL_ID, self::SEEK_VALUE_ALL_NAME, self::SEEK_VALUE_LOCATION_DIMENSION],
            self::SEEK_CHARACTER => [self::SEEK_VALUE_ALL_ID, self::SEEK_VALUE_ALL_NAME],
        ];

        if (!in_array($seekType, $supportedTypes, true)) {
            return false;
        }

        if (!in_array($findBy, $supportedSeeks[$seekType], true)) {
            return false;
        }

        return true;
    }

    /**
     * @return null|non-empty-array<Episode|Location|Character>
     */
    public function seek(string $seekType, string $findBy, mixed $value): ?array
    {
        if (!$this->supports($seekType, $findBy)) {
            return null;
        }

        $repo = $this->em->getRepository($seekType);
        if (method_exists($repo, $findBy)) {
            $result = $repo->$findBy($value);
        } elseif (method_exists($repo, 'findBy'.ucfirst($findBy))) {
            $findBy = 'findBy'.ucfirst($findBy);
            $result = $repo->$findBy($value);
        } else {
            $result = $repo->findBy([$findBy => $value]);
        }

        if (count($result) === 0) {
            return null;
        }

        return $result;
    }

    public function seekOne(string $seekType, string $findBy, mixed $value): null|Episode|Location|Character
    {
        $results = $this->seek($seekType, $findBy, $value);
        if (!$results) {
            return null;
        }

        return $results[0];
    }
}
