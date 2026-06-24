<?php

namespace App\Services;

use App\Models\CandidateSource;

class CandidateSourceService extends AdminCrudService
{
    protected function modelClass(): string
    {
        return CandidateSource::class;
    }
}
