<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModulePosts;

use App\Entity\Post;
use PHPUnit\Framework\TestCase;

final class PostEntityTest extends TestCase
{
    public function testNewPostIsNotAnonymousByDefault(): void
    {
        $post = new Post();

        $this->assertFalse($post->isIsAnonymous());
    }

    public function testCountLikesAndDislikesAreZeroWhenThereAreNoVotes(): void
    {
        $post = new Post();

        $this->assertSame(0, $post->countLikes());
        $this->assertSame(0, $post->countDislikes());
        $this->assertSame(0, $post->countShares());
    }
}
