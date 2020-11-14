<?php

namespace ethaniccc\Mockingbird\detections\combat\autoclicker;

use ethaniccc\Mockingbird\detections\Detection;
use ethaniccc\Mockingbird\user\User;
use ethaniccc\Mockingbird\utils\MathUtils;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;

class AutoClickerC extends Detection{

    private $clicks = 0;

    public function __construct(string $name, ?array $settings){
        parent::__construct($name, $settings);
        $this->vlThreshold = 45;
        $this->lowMax = 2;
        $this->mediumMax = 4;
    }

    public function handle(DataPacket $packet, User $user): void{
        if(($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY) || ($packet instanceof LevelSoundEventPacket && $packet->sound === LevelSoundEventPacket::SOUND_ATTACK_NODAMAGE)){
            if(++$this->clicks === $this->getSetting("samples")){
                $data = $user->clickData;
                $samples = $data->getTickSamples($this->getSetting("samples"));
                $kurtosis = MathUtils::getKurtosis($samples);
                $skewness = MathUtils::getSkewness($samples);
                $outlierPair = MathUtils::getOutliers($samples);
                $outliers = count($outlierPair->x) + count($outlierPair->y);
                if($kurtosis <= $this->getSetting("kurtosis") && $skewness <= $this->getSetting("skewness") && $outliers <= $this->getSetting("outliers")){
                    $this->fail($user, "kurtosis=$kurtosis skewness=$skewness outliers=$outliers cps={$data->cps}");
                }
                $this->clicks = 0;
            }
        }
    }

}