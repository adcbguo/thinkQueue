<?php
\think\Console::addDefaultCommands([
    "queue\\command\\Work",
    "queue\\command\\Listen",
]);