<?php

namespace TechStudio\Community\app\Repositories\Interfaces;

interface QuestionRepositoryInterface
{
   public function getQuestionList($data);
   public function createUpdate($data);
}