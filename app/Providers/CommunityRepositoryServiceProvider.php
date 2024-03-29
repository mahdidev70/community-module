<?php

namespace TechStudio\Community\app\Providers;

use Illuminate\Support\ServiceProvider;
use TechStudio\Community\app\Repositories\AnswerRepository;
use TechStudio\Community\app\Repositories\Interfaces\AnswerRepositoryInterface;
use TechStudio\Community\app\Repositories\Interfaces\JoinRepositoryInterface;
use TechStudio\Community\app\Repositories\Interfaces\QuestionRepositoryInterface;
use TechStudio\Community\app\Repositories\JoinRepository;
use TechStudio\Community\app\Repositories\QuestionRepository;

class CommunityRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(QuestionRepositoryInterface::class, QuestionRepository::class);
        $this->app->bind(AnswerRepositoryInterface::class, AnswerRepository::class);
        $this->app->bind(JoinRepositoryInterface::class, JoinRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
