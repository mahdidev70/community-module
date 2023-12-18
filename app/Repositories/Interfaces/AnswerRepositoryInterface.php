<?php


namespace TechStudio\Community\app\Repositories\Interfaces;

interface AnswerRepositoryInterface
{
   public function getAnswersList($data);
   public function createUpdate($data);
}