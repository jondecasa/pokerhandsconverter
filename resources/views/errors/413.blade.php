<x-error-page code="413" title="File too large"
    :message="'That file is bigger than this server accepts ('.round(\App\Support\UploadLimits::effectiveKb() / 1024, 1).' MB at most). Split the hand history into smaller files and try again.'" />
