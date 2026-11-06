<?php

declare(strict_types=1);

namespace App\Domains\Auth\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OceanExpertSearchService
{
    /**
     * Search user data by email via OceanExpert advancedSearch
     */
    public function searchExpertByEmail(string $email): array
    {
        $url = Config::get('services.oceanexpert.search_url');
        $url .= '?type[]=experts&keywords[]='.$email.'&filter[]=Email contains&toggele[]=OR&type[]=institution&keywords[]='.$email.'&filter[]=Email contains&action=advSearch';
        $response = Http::asJson()->get($url);
        $response->throw();
        $profile = [];
        if (isset($response['results']['data'][0])) {
            $profile['name'] = $response['results']['data'][0]['name'] ?? null;
            $profile['first_name'] = $response['results']['data'][0]['fname'] ?? null;
            $profile['last_name'] = $response['results']['data'][0]['sname'] ?? null;
            $profile['country'] = $response['results']['data'][0]['country'] ?? null;
            $profile['city'] = $response['results']['data'][0]['city'] ?? null;
        } else {
            throw new \RuntimeException('OceanExpert Profile Not found.');
        }

        return $profile;
    }

    /**
     * Search user data by email with fallback to any user type
     *
     * @throws \RuntimeException when no profile found in either search
     */
    public function searchUserByEmail(string $email): array
    {
        try {
            return $this->searchExpertByEmail($email);
        } catch (\RuntimeException $e) {
            Log::channel('auth')->info('Expert profile not found, attempting fallback search', [
                'email' => $email,
            ]);

            return $this->searchAnyByEmail($email);
        }
    }

    /**
     * Search user data by email via OceanExpert advancedSearch
     */
    public function searchAnyByEmail(string $email): array
    {
        $url = Config::get('services.oceanexpert.search_url');
        $response = Http::asJson()
            ->get($url, [
                'action' => 'advSearch',
                'filter[]' => 'Email contains',
                'keywords[]' => $email,
            ]);
        $response->throw();
        $profile = [];
        if (isset($response['results']['data'][0])) {
            $profile['name'] = $email;
            $profile['first_name'] = $email;
            $profile['last_name'] = null;
            $profile['country'] = null;
            $profile['city'] = null;
        } else {
            throw new \RuntimeException('OceanExpert Profile Not found.');
        }

        return $profile;
    }
}
