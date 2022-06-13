<?php

namespace vezdehod\toaster\pack;

use Exception;
use pocketmine\resourcepacks\ResourcePack;
use pocketmine\resourcepacks\ZippedResourcePack;
use Ramsey\Uuid\Uuid;
use vezdehod\toaster\pack\resource\IResource;
use vezdehod\toaster\ToastOptions;
use ZipArchive;
use function array_merge;
use function json_encode;
use function md5_file;
use function pathinfo;
use function unlink;
use const JSON_PRETTY_PRINT;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const PATHINFO_EXTENSION;

class RuntimePackBuilder {

    private const UUID_PACK_NAMESPACE = '010bcfd7-ab47-47c2-bc5b-82bbbd809906';
    private const UUID_RESOURCE_NAMESPACE = 'ae26160e-ced6-4467-9a91-a52e6172dc0b';

    //TODO: Split this to something like jsonui-builder
    private const TOAST_COMPONENT_NAME = 'vezdehodui_toast_component';
    private const FADE_IN_ANIMATION_NAME = 'vezdehodui_toast_anim_fade_in';
    private const STAY_ANIMATION_NAME = 'vezdehodui_toast_anim_stay';
    private const FADE_OUT_ANIMATION_NAME = 'vezdehodui_toast_anim_fade_out';

    private const ICON_PATH = "textures/vezdehodui/toast/icons/";
    private const SOUND_PATH = "sounds/vezdehodui/toast/";
    private const BACKGROUND_SLICE = "textures/vezdehodui/toast/background-slice";

    private ZipArchive $archive;
    private string $checksumSource = '';

    /** @var ToastOptions[] */
    private array $toasts = [];

    public function __construct(
        private string $path,
        string         $background,
        string         $backgroundSlice,
    ) {
        @unlink($this->path);
        $this->archive = new ZipArchive();
        $this->archive->open($this->path, ZipArchive::CREATE);

        $this->addFile(self::BACKGROUND_SLICE . "." . pathinfo($background, PATHINFO_EXTENSION), $background);
        $this->addFile(self::BACKGROUND_SLICE . ".json", $backgroundSlice);
    }


    /**
     * @throws Exception
     */
    public function addToast(ToastOptions $toast): void {
        $this->toasts[] = $toast;

        /** @var IResource $resource */
        foreach ([
                     self::ICON_PATH => $toast->getIcon(),
                     self::SOUND_PATH => $toast->getSound()
                 ] as $path => $resource) {
            $resource->fetch();

            $localFile = $resource->getLocalFile();
            if ($localFile === null) {
                continue;
            }
            $target = $toast->getPlugin()->getName() . "_" . $toast->getName() . "." . pathinfo($localFile, PATHINFO_EXTENSION);
            $this->addFile($path . $target, $localFile);
        }
    }

    public function generate(): ResourcePack {
        $this->injectSoundDefinitions();
        $this->injectJSONUI();
        $this->injectManifest();
        $this->archive->close();
        return new ZippedResourcePack($this->path);
    }

    private function addFile(string $archive, string $local): void {
        $this->archive->addFile($local, $archive);
        $this->checksumSource .= md5_file($local);
    }

    private function addFileContent(string $archive, string $content): void {
        $this->archive->addFromString($archive, $content);
        $this->checksumSource .= $content;
    }

    private function injectManifest(): void {
        $this->addFileContent("manifest.json", json_encode([
            'format_version' => 2,
            'header' => [
                'name' => "VToaster",
                'uuid' => Uuid::uuid3(self::UUID_PACK_NAMESPACE, $this->checksumSource)->toString(),
                'description' => "VToaster auto-generated resources",
                'version' => [1, 0, 0],
                'min_engine_version' => [1, 14, 0],
                'author' => 'vk.com/m.vezdehod'
            ],
            'modules' => [
                [
                    'type' => 'resources',
                    'uuid' => Uuid::uuid3(self::UUID_RESOURCE_NAMESPACE, $this->checksumSource)->toString(),
                    'version' => [1, 0, 0]
                ]
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function injectSoundDefinitions(): void {
        $sounds = [];
        foreach ($this->toasts as $toast) {
            $localFile = $toast->getSound()->getLocalFile();
            if ($localFile !== null) {
                $sounds[$toast->getSound()->getInPackUsageName()] = [
                    'category' => 'ui',
                    'sounds' => [
                        self::SOUND_PATH . $toast->getPlugin()->getName() . "_" . $toast->getName()
                    ]
                ];
            }
        }

        $this->addFileContent("sounds/sound_definitions.json", json_encode([
            'format_version' => '1.14.0',
            'sound_definitions' => $sounds
        ], JSON_UNESCAPED_SLASHES));
    }

    //TODO: animations, background, position

    private function injectJSONUI(): void {
        $this->addFileContent("ui/hud_screen.json", json_encode(array_merge(
            [
                'namespace' => 'hud'
            ],
            $this->createRootPanelModification(),
            $this->createToast(),
            $this->createFadeInAnimation(),
            $this->createStayAnimation(),
            $this->createFadeOutAnimation(),
        ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function createRootPanelModification(): array {
        return [
            'root_panel' => [
                'modifications' => [
                    [
                        'array_name' => 'controls',
                        'operation' => 'replace',
                        'control_name' => 'hud_actionbar_text_area',
                        'value' => [
                            [
                                'toast_factory' => [
                                    'type' => 'factory',
                                    'factory' => [
                                        'name' => 'hud_actionbar_text_factory',
                                        'control_ids' => [
                                            "hud_actionbar_text" => "impl@hud." . self::TOAST_COMPONENT_NAME
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }

    private function createToast(): array {//TODO: This should returns conditional-rendering style selector
        $details = [];
        $details['type'] = 'image';
        $details['texture'] = self::BACKGROUND_SLICE;
        $details['inherit_max_sibling_width'] = true;
        $details['min_size'] = [128, 32];
        $details['anchor_from'] = "top_right";
        $details['anchor_to'] = "top_right";
        $details['size'] = ["100%c + 10px", 32];
        $details['offset'] = '@hud.' . self::FADE_IN_ANIMATION_NAME;
        $details['layer'] = 50;

        $image = [];
        $image['type'] = 'image';
        $image['texture'] = '$texture';
        $image['$texture|default'] = 'textures/items/diamond_sword.png';
        $image['variables'] = [];
        foreach ($this->toasts as $toast) {
            $texture = $toast->getIcon()->getInPackUsageName();
            $localFile = $toast->getIcon()->getLocalFile();
            if ($localFile !== null) {
                $texture = self::ICON_PATH . $toast->getPlugin()->getName() . "_" . $toast->getName() . "." . pathinfo($localFile, PATHINFO_EXTENSION);
            }
            $image['variables'][] = [
                'requires' => "(not (\$actionbar_text = (\$actionbar_text - '{$toast->getFlag()}')))",
                '$texture' => $texture
            ];
        }
        $image['size'] = [24, 24];
        $image['offset'] = [3, 0];
        $image['anchor_from'] = "left_middle";
        $image['anchor_to'] = "left_middle";
        $image['layer'] = 51;

        $details['controls'] = [
            [
                'image' => $image
            ],
            [
                'label' => [
                    'type' => 'label',
                    'offset' => [30, 0],
                    'anchor_from' => 'left_middle',
                    'anchor_to' => 'left_middle',
                    'layer' => 51,
                    'text' => '$actionbar_text'
                ]
            ]
        ];

        return [self::TOAST_COMPONENT_NAME => $details];
    }

    //TODO: Animations, background, position

    private function createFadeInAnimation(): array {
        $details = [];
        $details['anim_type'] = 'offset';
        $details['duration'] = 1;
        $details['easing'] = 'spring';

        $details['from'] = '$from';
        $details['to'] = '$to';

        $details['$from|default'] = ["100%", 0];
        $details['$to|default'] = [0, 0];
        $details['variables'] = [[
            'requires' => '$pocket_screen',
            '$from' => ["100%", 10],
            '$to' => [0, 10],
        ]];

        $details['next'] = '@hud.' . self::STAY_ANIMATION_NAME;

        return [
            self::FADE_IN_ANIMATION_NAME => $details
        ];
    }

    private function createStayAnimation(): array {

        return [
            self::STAY_ANIMATION_NAME => [
                'duration' => 4,
                'anim_type' => 'wait',
                'next' => '@hud.' . self::FADE_OUT_ANIMATION_NAME
            ]
        ];
    }

    private function createFadeOutAnimation(): array {
        $details = [];
        $details['anim_type'] = 'offset';
        $details['duration'] = 1;
        $details['easing'] = 'in_sine';

        $details['from'] = '$from';
        $details['to'] = '$to';

        $details['$from|default'] = [0, 0];
        $details['$to|default'] = ["100%c + 12px", 0];
        $details['variables'] = [[
            'requires' => '$pocket_screen',
            '$from' => [0, 10],
            '$to' => ["100%c + 12px", 10],
        ]];

        $details['destroy_at_end'] = 'hud_actionbar_text';

        return [
            self::FADE_OUT_ANIMATION_NAME => $details
        ];
    }
}