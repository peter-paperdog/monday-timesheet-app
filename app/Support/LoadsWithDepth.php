<?php

namespace App\Support;
use Illuminate\Database\Eloquent\Model;

trait LoadsWithDepth
{
    protected function loadWithDepth(Model $model, int $depth, array $relationsMap): Model
    {
        $with = [];

        foreach ($relationsMap as $minDepth => $relations) {
            if ($depth >= $minDepth) {
                $with = array_merge($with, (array) $relations);
            }
        }

        return $model->load($with);
    }

    protected function getRelationsByDepth(int $depth): array
    {
        $with = [];

        foreach ($this->relationDepthMap ?? [] as $minDepth => $relations) {
            if ($depth >= $minDepth) {
                $with = array_merge($with, (array) $relations);
            }
        }

        return $with;
    }
}
