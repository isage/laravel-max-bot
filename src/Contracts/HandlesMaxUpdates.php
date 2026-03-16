<?php
namespace Blacky0892\Max\Contracts;

use Blacky0892\Max\Support\Update;

interface HandlesMaxUpdates
{
    public function handle(Update $update): void;
}
