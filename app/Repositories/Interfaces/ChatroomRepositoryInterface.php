<?php


namespace TechStudio\Community\app\Repositories\Interfaces;

interface ChatroomRepositoryInterface
{
    public function joinViaLink($userId,$room,$link);

    public function findByUserRoom($userId,$roomId);
}
