<?php

namespace Auroro\Support\Concerns;

trait HasUpdates
{
    /**
     * The update url to check for updates.
     *
     * @var string|null
     */
    protected $updateUrl = null;

    /**
     * Set the update url to check for updates.
     *
     * This is set as a public method so that it can be used to update
     * the URL later on the application lifecycle, if needed.
     *
     * Not sure if this is necessary, but it's here for now.
     *
     * @todo Reevaluate if this is necessary.
     *
     * @since 0.1.0
     * @param string $url The new update url.
     * @return static
     */
    public function useUpdateUrl(string $url): static
    {
        $this->updateUrl = $url;

        return $this;
    }

    /**
     * Get the update url to check for updates.
     *
     * @since 0.1.0
     * @return string|null
     */
    public function getUpdateUrl()
    {
        return $this->updateUrl;
    }
}
