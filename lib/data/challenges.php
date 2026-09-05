<?php
// ================================================
// lib/data/challenges.php — Command & Build challenges
// ------------------------------------------------
// Small prompts for the Surprise Me feature. Command challenges
// name the tool that solves them so the user is never stuck.
// ================================================

function commandChallenges(): array
{
    return [
        ['task' => 'Give every player within 10 blocks Speed II for 30 seconds.', 'tool' => 'commands.php?t=effect'],
        ['task' => 'Put everyone into creative mode in one command.', 'tool' => 'commands.php?t=gamemode'],
        ['task' => 'Clear a 19 × 19 area down to bedrock-safe level without touching the ground you are standing on.', 'tool' => 'build.php?t=clear'],
        ['task' => 'Teleport yourself 50 blocks straight up using relative coordinates.', 'tool' => 'commands.php?t=teleport'],
        ['task' => 'Set the time to just before sunset and stop the day cycle there.', 'tool' => 'commands.php?t=time'],
        ['task' => 'Give yourself a named, unbreakable pickaxe with Efficiency V.', 'tool' => 'nbt.php'],
        ['task' => 'Turn on keepInventory and turn off mob griefing in two commands.', 'tool' => 'commands.php?t=gamerule'],
        ['task' => 'Build a 15 × 15 hollow stone brick box in a single command.', 'tool' => 'build.php?t=fill'],
        ['task' => 'Replace every dirt block in a 30 × 10 × 30 region with grass.', 'tool' => 'build.php?t=replace'],
        ['task' => 'Summon a lightning bolt exactly where you are looking.', 'tool' => 'commands.php?t=summon'],
        ['task' => 'Remove every dropped item in the world without killing any mobs.', 'tool' => 'commands.php?t=kill'],
        ['task' => 'Give a player 30 experience levels, not points.', 'tool' => 'commands.php?t=experience'],
        ['task' => 'Copy a 10 × 5 × 10 building 20 blocks to the east.', 'tool' => 'build.php?t=clone'],
        ['task' => 'Find the nearest ancient city and note how far away it is.', 'tool' => 'commands.php?t=locate'],
    ];
}

function buildChallenges(): array
{
    return [
        'Build a house using exactly four block types, no more.',
        'Build something with no flat wall longer than five blocks.',
        'Build a tower that changes width at least three times.',
        'Build a bridge with a visible arch under it.',
        'Build a shop front that reads as a shop without any signs.',
        'Build a house on a slope without flattening the ground.',
        'Build something using only blocks you can get in the first ten minutes of a world.',
        'Build a ruin — decide what it used to be first.',
        'Build a small home for one specific villager profession.',
        'Redesign your oldest build using what you know now.',
        'Build a structure that is more interesting from below than from the side.',
        'Build using a palette with no wood in it at all.',
    ];
}
