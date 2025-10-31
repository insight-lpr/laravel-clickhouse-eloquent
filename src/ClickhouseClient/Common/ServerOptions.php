<?php

namespace LaravelClickhouseEloquent\ClickhouseClient\Common;

/**
 * Container to server options.
 */
class ServerOptions
{
    /**
     * Protocol.
     *
     * @var string
     */
    protected $protocol = 'http';

    /**
     * Tags.
     *
     * @var array
     */
    protected $tags = [];

    /**
     * Sets protocol.
     */
    public function setProtocol(string $protocol): self
    {
        $this->protocol = $protocol;

        return $this;
    }

    /**
     * Returns protocol.
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * Set tags.
     */
    public function setTags(array $tags): self
    {
        $this->tags = [];

        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    /**
     * Adds tag.
     */
    public function addTag(string $tag): self
    {
        $this->tags[$tag] = true;

        return $this;
    }

    /**
     * Returns tags.
     */
    public function getTags(): array
    {
        return array_keys($this->tags);
    }
}
