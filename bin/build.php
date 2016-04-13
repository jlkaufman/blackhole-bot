#!/usr/bin/env php
<?php
const PHAR_FILE = __DIR__ . '/../blackhole-bot.phar';
const EXEC_FILE = __DIR__ . '/../blackhole-bot';

$phar = new Phar(PHAR_FILE, 0, 'blackhole-bot.phar');

/**
 * Add files to phar
 */
$append = new AppendIterator();

$append->append(
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../src/', FilesystemIterator::SKIP_DOTS)
    )
);

$append->append(
    new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(__DIR__ . '/../vendor/', FilesystemIterator::SKIP_DOTS)
    )
);


$append->append(new ArrayIterator(
    [
        'src/Config/Services/services.yml' => realpath(__DIR__ . '/../src/Config/Services/services.yml'),
    ]
));

$phar->addFromString(
    'bin/blackhole',
    str_replace('#!/usr/bin/env php', '', file_get_contents(__DIR__ . '/../bin/blackhole'))
);

/**
 * Build the phar
 */

$phar->buildFromIterator($append, __DIR__ . '/..');


// start buffering. Mandatory to modify stub.
$phar->startBuffering();

// Get the default stub. You can create your own if you have specific needs
$defaultStub = $phar->createDefaultStub('bin/blackhole');

// Adding files
$phar->buildFromDirectory(__DIR__, '/\.php$/');

// Create a custom stub to add the shebang
$stub = "#!/usr/bin/env php \n" . $defaultStub;

// Add the stub
$phar->setStub($stub);

$phar->stopBuffering();


chmod(PHAR_FILE, 0755);

rename(PHAR_FILE, EXEC_FILE);