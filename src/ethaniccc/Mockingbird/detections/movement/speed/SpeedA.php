<?php

namespace ethaniccc\Mockingbird\detections\movement\speed;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\detections\movement\CancellableMovement;
use ethaniccc\Mockingbird\user\User;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;

class SpeedA extends Detection implements CancellableMovement{

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
    }

    public function handle(DataPacket $packet, User $user): void{
        if($packet instanceof PlayerAuthInputPacket){
            if($user->moveData->offGroundTicks > 3){
                $lastMoveDelta = $user->moveData->lastMoveDelta;
                $currentMoveDelta = $user->moveData->moveDelta;
                if($lastMoveDelta === null){
                    return;
                }
                $lastXZ = hypot($lastMoveDelta->x, $lastMoveDelta->z);
                $currentXZ = hypot($currentMoveDelta->x, $currentMoveDelta->z);
                $expectedXZ = $lastXZ * 0.91 + 0.026;
                $equalness = $currentXZ - $expectedXZ;
                if($equalness > $this->getSetting("max_breach")
                && $user->timeSinceStoppedFlight >= 20
                && $user->timeSinceTeleport >= 5
                && $user->timeSinceMotion >= 10){
                    if(++$this->preVL >= 3){
                        $this->fail($user, "e=$equalness cXZ=$currentXZ lXZ=$lastXZ");
                    }
                } else {
                    $this->preVL = 0;
                    $this->reward($user, 0.999);
                }
            }
        }
    }

}