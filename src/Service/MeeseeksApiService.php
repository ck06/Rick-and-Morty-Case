<?php declare(strict_types=1);

namespace App\Service;

use NickBeen\RickAndMortyPhpApi\Character;
use NickBeen\RickAndMortyPhpApi\Dto\Character as CharacterDto;
use NickBeen\RickAndMortyPhpApi\Dto\Episode as EpisodeDto;
use NickBeen\RickAndMortyPhpApi\Dto\Location as LocationDto;
use NickBeen\RickAndMortyPhpApi\Episode;
use NickBeen\RickAndMortyPhpApi\Exceptions\NotFoundException;
use NickBeen\RickAndMortyPhpApi\Location;
use RuntimeException;

class MeeseeksApiService
{
    // supported seekTypes
    public const SEEK_EPISODE = Episode::class;
    public const SEEK_LOCATION = Location::class;
    public const SEEK_CHARACTER = Character::class;

    // supported findBy options per seekType
    public const SEEK_VALUE_ALL_NAME = 'withName';
    public const SEEK_VALUE_ALL_ID = 'get';

    public const SEEK_VALUE_EPISODE_CODE = 'withEpisode';
    public const SEEK_VALUE_LOCATION_DIMENSION = 'withDimension';

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
     * @return null|non-empty-array<EpisodeDto|LocationDto|CharacterDto>
     */
    public function seek(string $seekType, string $findBy, mixed $value): ?array
    {
        if (!$this->supports($seekType, $findBy)) {
            return null;
        }

        $searcher = new $seekType();
        if (!method_exists($searcher, $findBy)) {
            throw new RuntimeException(
                sprintf(
                    'An error occurred trying to use the RnM library: method %s does not exist.',
                    "$seekType->$findBy()",
                ),
            );
        }

        try {
            $results = $searcher->$findBy($value);
            if ($findBy !== self::SEEK_VALUE_ALL_ID) {
                $results = $results->get();
            }

            if (str_contains(get_class($results), '\\Dto\\Collection')) {
                // for consistency, return array of DTOs rather than the DtoCollection.
                return $results->results;
            }

            if (str_contains(get_class($results), '\\Dto\\')) {
                // for consistency, wrap our single result DTO in an array
                return [$results];
            }

            // debug statement in case we encounter other result styles.
            // TODO: remove later
            dd('MeeseeksApiService', $results);
        } catch (NotFoundException) {
            return null;
        }
    }

    public function seekOne(string $seekType, string $findBy, mixed $value): null|EpisodeDto|LocationDto|CharacterDto
    {
        $results = $this->seek($seekType, $findBy, $value);
        if (!$results) {
            return null;
        }

        return $results[0];
    }
}
