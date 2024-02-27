<?php


namespace TechStudio\Community\app\Repositories\Interfaces;

interface JoinRepositoryInterface
{
    public function findById($id);
    public function joinViaLink($userId,$room,$link);
    public function findByUserRoom($userId,$roomId);
    public function getFilterUsersByLinkJoin($request);

    public function editStatus($request);
}
