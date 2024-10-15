<?php

namespace App\Services\System;

class InspiringService
{
    private array $messages = [
        "Every line of code is a step towards something extraordinary. Embarking on this Laravel project isn't just about building an application; it's about crafting an experience that will make a difference. Embrace each challenge as an opportunity to innovate and turn ideas into reality. Remember: success is the sum of small efforts repeated day in and day out. Keep moving forward with passion and purpose, and the result will be nothing short of incredible!",
        "In Laravel, every problem is a puzzle waiting to be solved. Approach each task with creativity and determination, and watch as your vision transforms into something truly remarkable. Keep pushing forward—your masterpiece is within reach!",
        "As you build this project in Laravel, remember: great things take time, but they’re worth every second. Stay focused, trust your process, and let your passion drive you to create something that will stand the test of time.",
        "With Laravel as your canvas, the possibilities are endless. Don’t just code—create, innovate, and bring your boldest ideas to life. The journey may be challenging, but the destination will be worth every effort.",
        "Every feature you implement is a building block towards greatness. In Laravel, you have the tools to shape the future—so dream big, code with confidence, and turn your aspirations into achievements.",
        "Think of your Laravel project as a journey of discovery. Each bug you fix, each function you optimize, is a step closer to creating something extraordinary. Stay curious, stay committed, and let your passion fuel your progress.",
        "Coding in Laravel is like crafting a story where you control the narrative. Every detail matters. Write your code with intention, and soon you'll have a tale of innovation and excellence that others will admire.",
        "Innovation starts with a single line of code. With Laravel, you're not just building a project—you're setting the stage for something transformative. Keep your goals in sight and your motivation high, and the result will be spectacular.",
        "Laravel gives you the freedom to build anything your mind can conceive. So dream without limits, code without fear, and watch as your ideas evolve into something truly remarkable.",
        "In Laravel, each challenge is an opportunity to grow, and every solution brings you closer to your vision. Embrace the process, trust your skills, and know that what you're building has the power to inspire and impact others."
    ];

    public static function getMessage(): string
    {
        $instance = new self();
        $message = $instance->messages[rand(0, count($instance->messages)-1)];
        return str_replace('. ', '.'.PHP_EOL, $message);
    }
}
