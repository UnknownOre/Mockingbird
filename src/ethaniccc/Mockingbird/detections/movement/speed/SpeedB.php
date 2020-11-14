<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\block\Ice;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedB extends Detection implements CancellableMovement{

    private $onGroundTicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            $theoreticalOnGround = fmod(($posY = round($user->moveData->location->y, 4)), 1 / 64) === 0.0;
            if($theoreticalOnGround){
                ++$this->onGroundTicks;
            } else {
                $this->onGroundTicks = 0;
            }
            $horizontalSpeed = hypot($user->moveData->moveDelta->x, $user->moveData->moveDelta->z);
            if($user->timeSinceStoppedFlight >= 20
            && $user->moveData->blockAbove->getId() === 0){
                $maxSpeed = $this->onGroundTicks >= 10 ? $this->getSetting("max_speed_on_ground") : $this->getSetting("max_speed_off_ground");
                if($user->moveData->blockBelow instanceof Ice){
                    $maxSpeed *= 5/3;
                }
                if($user->player->getEffect(1) !== null){
                    $amplifier = $user->player->getEffect(1)->getAmplifier() + 1;
                    $maxSpeed += 0.2 * $amplifier;
                }
                if($horizontalSpeed > $maxSpeed && $user->timeSinceTeleport >= 10 && $user->timeSinceMotion >= 20){
                    if(++$this->preVL >= 2){
                        $this->fail($user, "speed=$horizontalSpeed tpTime={$user->timeSinceTeleport}");
                    }
                } else {
                    $this->preVL = 0;
                    $this->reward($user, 0.999);
                }
            }
        }
    }

}