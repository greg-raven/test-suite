<?php

namespace Hp;

//  PROJECT HONEY POT ADDRESS DISTRIBUTION SCRIPT
//  For more information visit: http://www.projecthoneypot.org/
//  Copyright (C) 2004-2021, Unspam Technologies, Inc.
//
//  This program is free software; you can redistribute it and/or modify
//  it under the terms of the GNU General Public License as published by
//  the Free Software Foundation; either version 2 of the License, or
//  (at your option) any later version.
//
//  This program is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//  GNU General Public License for more details.
//
//  You should have received a copy of the GNU General Public License
//  along with this program; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
//  02111-1307  USA
//
//  If you choose to modify or redistribute the software, you must
//  completely disconnect it from the Project Honey Pot Service, as
//  specified under the Terms of Service Use. These terms are available
//  here:
//
//  http://www.projecthoneypot.org/terms_of_service_use.php
//
//  The required modification to disconnect the software from the
//  Project Honey Pot Service is explained in the comments below. To find the
//  instructions, search for:  *** DISCONNECT INSTRUCTIONS ***
//
//  Generated On: Sun, 16 May 2021 08:34:10 -0400
//  For Domain: www.heeled.website
//
//

//  *** DISCONNECT INSTRUCTIONS ***
//
//  You are free to modify or redistribute this software. However, if
//  you do so you must disconnect it from the Project Honey Pot Service.
//  To do this, you must delete the lines of code below located between the
//  *** START CUT HERE *** and *** FINISH CUT HERE *** comments. Under the
//  Terms of Service Use that you agreed to before downloading this software,
//  you may not recreate the deleted lines or modify this software to access
//  or otherwise connect to any Project Honey Pot server.
//
//  *** START CUT HERE ***

define('__REQUEST_HOST', 'hpr4.projecthoneypot.org');
define('__REQUEST_PORT', '80');
define('__REQUEST_SCRIPT', '/cgi/serve.php');

//  *** FINISH CUT HERE ***

interface Response
{
    public function getBody();
    public function getLines(): array;
}

class TextResponse implements Response
{
    private $content;

    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getBody()
    {
        return $this->content;
    }

    public function getLines(): array
    {
        return explode("\n", $this->content);
    }
}

interface HttpClient
{
    public function request(string $method, string $url, array $headers = [], array $data = []): Response;
}

class ScriptClient implements HttpClient
{
    private $proxy;
    private $credentials;

    public function __construct(string $settings)
    {
        $this->readSettings($settings);
    }

    private function getAuthorityComponent(string $authority = null, string $tag = null)
    {
        if(is_null($authority)){
            return null;
        }
        if(!is_null($tag)){
            $authority .= ":$tag";
        }
        return $authority;
    }

    private function readSettings(string $file)
    {
        if(!is_file($file) || !is_readable($file)){
            return;
        }

        $stmts = file($file);

        $settings = array_reduce($stmts, function($c, $stmt){
            list($key, $val) = \array_pad(array_map('trim', explode(':', $stmt)), 2, null);
            $c[$key] = $val;
            return $c;
        }, []);

        $this->proxy       = $this->getAuthorityComponent($settings['proxy_host'], $settings['proxy_port']);
        $this->credentials = $this->getAuthorityComponent($settings['proxy_user'], $settings['proxy_pass']);
    }

    public function request(string $method, string $uri, array $headers = [], array $data = []): Response
    {
        $options = [
            'http' => [
                'method' => strtoupper($method),
                'header' => $headers + [$this->credentials ? 'Proxy-Authorization: Basic ' . base64_encode($this->credentials) : null],
                'proxy' => $this->proxy,
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $body = file_get_contents($uri, false, $context);

        if($body === false){
            trigger_error(
                "Unable to contact the Server. Are outbound connections disabled? " .
                "(If a proxy is required for outbound traffic, you may configure " .
                "the honey pot to use a proxy. For instructions, visit " .
                "http://www.projecthoneypot.org/settings_help.php)",
                E_USER_ERROR
            );
        }

        return new TextResponse($body);
    }
}

trait AliasingTrait
{
    private $aliases = [];

    public function searchAliases($search, array $aliases, array $collector = [], $parent = null): array
    {
        foreach($aliases as $alias => $value){
            if(is_array($value)){
                return $this->searchAliases($search, $value, $collector, $alias);
            }
            if($search === $value){
                $collector[] = $parent ?? $alias;
            }
        }

        return $collector;
    }

    public function getAliases($search): array
    {
        $aliases = $this->searchAliases($search, $this->aliases);
    
        return !empty($aliases) ? $aliases : [$search];
    }

    public function aliasMatch($alias, $key)
    {
        return $key === $alias;
    }

    public function setAlias($key, $alias)
    {
        $this->aliases[$alias] = $key;
    }

    public function setAliases(array $array)
    {
        array_walk($array, function($v, $k){
            $this->aliases[$k] = $v;
        });
    }
}

abstract class Data
{
    protected $key;
    protected $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function key()
    {
        return $this->key;
    }

    public function value()
    {
        return $this->value;
    }
}

class DataCollection
{
    use AliasingTrait;

    private $data;

    public function __construct(Data ...$data)
    {
        $this->data = $data;
    }

    public function set(Data ...$data)
    {
        array_map(function(Data $data){
            $index = $this->getIndexByKey($data->key());
            if(is_null($index)){
                $this->data[] = $data;
            } else {
                $this->data[$index] = $data;
            }
        }, $data);
    }

    public function getByKey($key)
    {
        $key = $this->getIndexByKey($key);
        return !is_null($key) ? $this->data[$key] : null;
    }

    public function getValueByKey($key)
    {
        $data = $this->getByKey($key);
        return !is_null($data) ? $data->value() : null;
    }

    private function getIndexByKey($key)
    {
        $result = [];
        array_walk($this->data, function(Data $data, $index) use ($key, &$result){
            if($data->key() == $key){
                $result[] = $index;
            }
        });

        return !empty($result) ? reset($result) : null;
    }
}

interface Transcriber
{
    public function transcribe(array $data): DataCollection;
    public function canTranscribe($value): bool;
}

class StringData extends Data
{
    public function __construct($key, string $value)
    {
        parent::__construct($key, $value);
    }
}

class CompressedData extends Data
{
    public function __construct($key, string $value)
    {
        parent::__construct($key, $value);
    }

    public function value()
    {
        $url_decoded = base64_decode(str_replace(['-','_'],['+','/'],$this->value));
        if(substr(bin2hex($url_decoded), 0, 6) === '1f8b08'){
            return gzdecode($url_decoded);
        } else {
            return $this->value;
        }
    }
}

class FlagData extends Data
{
    private $data;

    public function setData($data)
    {
        $this->data = $data;
    }

    public function value()
    {
        return $this->value ? ($this->data ?? null) : null;
    }
}

class CallbackData extends Data
{
    private $arguments = [];

    public function __construct($key, callable $value)
    {
        parent::__construct($key, $value);
    }

    public function setArgument($pos, $param)
    {
        $this->arguments[$pos] = $param;
    }

    public function value()
    {
        ksort($this->arguments);
        return \call_user_func_array($this->value, $this->arguments);
    }
}

class DataFactory
{
    private $data;
    private $callbacks;

    private function setData(array $data, string $class, DataCollection $dc = null)
    {
        $dc = $dc ?? new DataCollection;
        array_walk($data, function($value, $key) use($dc, $class){
            $dc->set(new $class($key, $value));
        });
        return $dc;
    }

    public function setStaticData(array $data)
    {
        $this->data = $this->setData($data, StringData::class, $this->data);
    }

    public function setCompressedData(array $data)
    {
        $this->data = $this->setData($data, CompressedData::class, $this->data);
    }

    public function setCallbackData(array $data)
    {
        $this->callbacks = $this->setData($data, CallbackData::class, $this->callbacks);
    }

    public function fromSourceKey($sourceKey, $key, $value)
    {
        $keys = $this->data->getAliases($key);
        $key = reset($keys);
        $data = $this->data->getValueByKey($key);

        switch($sourceKey){
            case 'directives':
                $flag = new FlagData($key, $value);
                if(!is_null($data)){
                    $flag->setData($data);
                }
                return $flag;
            case 'email':
            case 'emailmethod':
                $callback = $this->callbacks->getByKey($key);
                if(!is_null($callback)){
                    $pos = array_search($sourceKey, ['email', 'emailmethod']);
                    $callback->setArgument($pos, $value);
                    $this->callbacks->set($callback);
                    return $callback;
                }
            default:
                return new StringData($key, $value);
        }
    }
}

class DataTranscriber implements Transcriber
{
    private $template;
    private $data;
    private $factory;

    private $transcribingMode = false;

    public function __construct(DataCollection $data, DataFactory $factory)
    {
        $this->data = $data;
        $this->factory = $factory;
    }

    public function canTranscribe($value): bool
    {
        if($value == '<BEGIN>'){
            $this->transcribingMode = true;
            return false;
        }

        if($value == '<END>'){
            $this->transcribingMode = false;
        }

        return $this->transcribingMode;
    }

    public function transcribe(array $body): DataCollection
    {
        $data = $this->collectData($this->data, $body);

        return $data;
    }

    public function collectData(DataCollection $collector, array $array, $parents = []): DataCollection
    {
        foreach($array as $key => $value){
            if($this->canTranscribe($value)){
                $value = $this->parse($key, $value, $parents);
                $parents[] = $key;
                if(is_array($value)){
                    $this->collectData($collector, $value, $parents);
                } else {
                    $data = $this->factory->fromSourceKey($parents[1], $key, $value);
                    if(!is_null($data->value())){
                        $collector->set($data);
                    }
                }
                array_pop($parents);
            }
        }
        return $collector;
    }

    public function parse($key, $value, $parents = [])
    {
        if(is_string($value)){
            if(key($parents) !== NULL){
                $keys = $this->data->getAliases($key);
                if(count($keys) > 1 || $keys[0] !== $key){
                    return \array_fill_keys($keys, $value);
                }
            }

            end($parents);
            if(key($parents) === NULL && false !== strpos($value, '=')){
                list($key, $value) = explode('=', $value, 2);
                return [$key => urldecode($value)];
            }

            if($key === 'directives'){
                return explode(',', $value);
            }

        }

        return $value;
    }
}

interface Template
{
    public function render(DataCollection $data): string;
}

class ArrayTemplate implements Template
{
    public $template;

    public function __construct(array $template = [])
    {
        $this->template = $template;
    }

    public function render(DataCollection $data): string
    {
        $output = array_reduce($this->template, function($output, $key) use($data){
            $output[] = $data->getValueByKey($key) ?? null;
            return $output;
        }, []);
        ksort($output);
        return implode("\n", array_filter($output));
    }
}

class Script
{
    private $client;
    private $transcriber;
    private $template;
    private $templateData;
    private $factory;

    public function __construct(HttpClient $client, Transcriber $transcriber, Template $template, DataCollection $templateData, DataFactory $factory)
    {
        $this->client = $client;
        $this->transcriber = $transcriber;
        $this->template = $template;
        $this->templateData = $templateData;
        $this->factory = $factory;
    }

    public static function run(string $host, int $port, string $script, string $settings = '')
    {
        $client = new ScriptClient($settings);

        $templateData = new DataCollection;
        $templateData->setAliases([
            'doctype'   => 0,
            'head1'     => 1,
            'robots'    => 8,
            'nocollect' => 9,
            'head2'     => 1,
            'top'       => 2,
            'legal'     => 3,
            'style'     => 5,
            'vanity'    => 6,
            'bottom'    => 7,
            'emailCallback' => ['email','emailmethod'],
        ]);

        $factory = new DataFactory;
        $factory->setStaticData([
            'doctype' => '<!DOCTYPE html>',
            'head1'   => '<html><head>',
            'head2'   => '<title>fluty fairtrade www.heeled.website deranged dogma</title></head>',
            'top'     => '<body><div align="center">',
            'bottom'  => '</div></body></html>',
        ]);
        $factory->setCompressedData([
            'robots'    => 'H4sIAAAAAAAAA7PJTS1JVMhLzE21VSrKT8ovKVZSSM7PK0nNK7FVSsvPyckv18nLz8xLSa1QsrPBpzgvP7EoOSOzLFXJDgC9o9kCVQAAAA',
            'nocollect' => 'H4sIAAAAAAAAA7PJTS1JVMhLzE21VcrL103NTczM0U3Oz8lJTS7JzM9TUkjOzytJzSuxVdJXsgMAKsBXli0AAAA',
            'legal'     => 'H4sIAAAAAAAAA61bbW_bOLb-fn8Fb3qR7QBpmrRNm1xlArip23oxk3Ztt4P9SEm0zYkkakjKrvvr97yQenFcT7HIAE4tWSJ53p7znEPOtZdpoUSmiqKWea6r5a9HZ0ciNTZXlr7iT66WWfjp5trbm_-59vnNNdythPPbQv16tDCVf7aQpS62_y8y01it7IkoTWXwXZUc3RxXqauT6_TmGp-FZwpjf33ynv67kdfP8e7N9fM0PMh_xXVq2zftzXylxAa_-OMn5-fnidgavDJ49TpZKYtXf6jUwY2zC3rFKpnTKHKpKk_vrlR5il8qUz3jN8Nr1__77Jnw21pnshBWblJTqVzoDP48e3bjND7WLUg8WOg-yRZRMvh9VquM5rBqbYrGa1NJu4UXKm9lKp0Tqa5wKprpDpb3Eb-4WqM8Z4nCq9qaJckpG2_KuKRxefzk6k2iCxofR5S6UhYHw58nXmhHL-FIlwkIhlcr6YSkKei1XH__DstpvAdjw9UahsB_lHVKmFRpJ0E-GNIbS6-tZdHggOekQedplLopa9YYGwn_vkp0O_vLxCq1o8ofuEX2wC1-wn-GLjOW2SrYn1UDfsAa1ylZQOSN3-JqQWH43NOjfzZ59LGzV4kSpG2U4iJp4mBVLvDOC5rDLMRC8UNgg2oLOmV1WpWRkQvhtlW2sqZUbhVtMlOsmbUOXsmm6JZ_WDcPQ2Yo-L9Ns--1OrwGS72i50q55Yll2rjOmF91CCIfF_V1cvzk8mUymePF59F0_m8WGe59eTs7fvKax7t-jrgA_wBE_Pc4IQ4LX-_YGz8Q9roL573Cr8JrDrz26rKnMK8shRJ7sMyyBlweQr_KdK5omCpvMvJ9FUCHp2IfYbWV5BpOIBydvehj1zy6EiGSrNyidbDz5PRvYSV6FoCSilO_oUmvElntE9T3UMe6E9GKK0snGEzoygPUDaeX_Gtuu1vgxSTxqUCf-gHOmTAjQgI-s1Qn7FY8BoQIOVLFCCD8inyPNQCDDRfxA6urQ1mCsFFySPkt41GYRmc64B75yVI7WkshN45eVXZhSF7IeZwDrJbVsimkFaX8psumJIAo8BmKaPrCiz9-8up1IigWUga8tRJ7TfozKNfZG0eqnKKpasYaRYtz98oDDC0LTBjOAxRJm4OkVi8AyKxaNEulGH7BZyxbDjInIZV1Hcp8gth9kVAAi1G32k9TMcOrf30Z380fMaJ_IP19lP6nDN7TD-heLCx55p98mfkQgUuzJgyASG5xLpOgmgGudyRB0lP0Rzn2CFJeyum0Mhbg2VZCfdNAlmrIFAAYDqhCZiCBpJgf7X3E9Z8JZ-ctz54FDH2daKd9j8xwvgHnehWAhCQkhIP4Ia8oim2LHco5Xvmy0t9VL5yz-6qnr8pwFObP2eFXyrGzaif-6MBfVowCYjdP7xVmn-WWwXJy2UIWzm84IHmAUeY7E4RkyM_l7Ogyr0Au0DdEstsgGUGvbv0ADCrITAQlANd9uav8h5RMh7WlluHMNIIBTi55EQ_oyV6xAV1MD-MdJPfuagJpg4RYyAKSBwSqtiAIeGghgWPlymWqotQSvCYwI6aHernyQpapBqxp4F1V6oVGGoH5p4cuT96cJ9PxmG6M4eoS9Pf6MpncdQt5Ox2PHi-ID4dyfjBcrxKiTFfJigmPCegMgcXMSzHK-UMYSe7bFIzm7P4VElWOe1AcDRQm6tGB7nMz-zy-nfSxFgHdBXRkNKeICDGgvsW1YUB4MxjtgHcBMNBiEZoJcs2mUHlnmEI5jvHuVh4SU5uDyaGA1nj68q22HOM9z_xpS7ROowC3GAFZ-YKVb6qjX-iZUV7ynboudAcJlde8HC4VUpkvAfbAN2tQnoFRgPRD1izEWhcFcmwsEyg6efHk0TRQIJlVZxz2DjL_WcK3f4oUrWgpplbVQoYiTa558RvAa8XGZTamVaQfkFf3utii72L4mX-EaLpI5jTfB0EXd5Tobz-KT-8fMTUeNuZqDz_IVod8b8ticiwsDPGWJdLaqG3AuAD8QbEoG-t_S7fzRhZPScce0il9MU1H00IojX47lLV_G0RZ4IEVe0Fb3Cqkpz3cZtC0UhcCKmSwIqjTA1A7TLh_Gsh5aOYsU_2lsEiQwyg89lo3JiTPRAgcVFbdtBCidIVFIa3gu4Qy7l5XOczndS02xgBak1NTEDjFwUt--g8nFJUPDp6uf8ZzOTAGS4AsQVduJYuCU7LnKjuPiQZG3-j-G1wIUCZfAilxPYGMGGpo19GmfdssD0HulrNktN8hDyXd2EPBpZlxGwEG7aWxF8lo-nXcTfM7lZOz6AxNDnlU-xVgpiNKYBViIaRPqGCAbgOtUaJUdskdD35vI4Gkbdv8yoUYdyiQwGGLA5yKEmwFGHavCUA-jmePF9g_COiojPl4usv53yT9ML1MOJA3zJM4S4L43MeCmrXLIF236Bex6fCdCQ1HVaGBqUrqeYCgle_AHZsPA3eBhYMF3iTju9n4YRCvNffCeMEljw4FiFIbuRVNpcq6MFsA5BSYGxYnOB1X1rmoC1lBXeUaW1sYB6xgzRbyBrG8Ab9jMlRqVyiJnUn81aQmBOAJ92XiB3JkaGWpEIy5iO2mbuVfZuL_6GnQQYeRgbJVnKkQemgka1Y67aPd589DSNuCFLTGbVOW27ZxF4sRbbrBUy43XiZtTjq_DGQ3dKf61DMkaF3Rb4DAHPZehPskX6Eyb3eMNr79xDkxBddeS-5ceLkkL_fauUZh81EV7AamyL0uKVdP3_Uls2LCzdKVZhdjWGs4Rei_eojgV7olJbP55O4DkYgP46cUR2NY8uWrBBz9EYIKBPx9dgjcOUUPmkLOxZKCciBj5lkbG-fYiujXDfxzHmsu9G_u6gXcyrj4bzEZnnlxlgAasbNSl1kK5QCRrM7wcQ2MSxZwWTFYQdlxzzjldqx3KKVPx7P5gLQamhCC7S9I1RBrXkmBnZqyhMKijXL7D5rka6wuIWwychoIKSouD5bdveUFAZtqYRooXHLsP-RdvHJfGyBYUxBzOaUZwziaoG5hvn9xdsrtkoXo99CYn8XmSs4U2IayN0R4ftrTFgHU6G3w98IwQPw2wCt_whUiIWoVywKus9dgEfxyawIVlTF4gYtWnqnEKsToqLu6CL7Agmt2GEEz0EhY1e8Spdmggw3c8UGFBItRHfl_Ceo40PMlZqaADpQ9z83zFg1OxIdRTHU2B08ATq6WALKygaoasihkPr2sDBH2tgB9BXLOPx0sJ-C532egxjdXyaPwXzbjZTJQz-2nu4c5B2CGBFLfgBVljYXgov4cxJtCetjGUx7a1n30xS0hlvHqCikMacZgy8RhRGYSIHZR6LoO-xu55qBh7GN_YR-xpuhyLjAjQUgIAG31jsmnk9v5IFsc7sZjoOisbe-eJ0B3ec9kwz-GBgnegiDnUnkwZ-V0Tu_necgl7Y4P1lkH4lxxfRr4x6IJaVQSZkrugUHabpzGvUKpy17ct73wC5zvwCRdq-dvEC82BH8bQep4kzwMFcI2Sk4CCk8KBaAaCMSczjMvnvrO-NT7Vp72DbG2FZ01V4qrOo7dSmUsuV3vGpM6dJMZOP7LZP7QOT-zwo0fDH1E6vNZD9-QvHE9Ggf4OJ7SqNyywVxJdHgsJt3ws_H06yNF27vJnKajEH4oSGzXVe5UzAPonoqodNfzzkALgsTYpZQN9xa5d6eLe2AtVQ4FkdVrznxLK7GupAhjXGXzeR9b6QSlO7r_FHpiKQiFHLGQKQiH3cG8UKnM7sH6deg43A2B9n1fwgpcmleYFbzDB8mQWzPFXrSNjmgGLMvidGEXV2YkV0BQp0JznWBmFWgotY7J96BiIQ2G7hXQN3rrEC5YdsmeNkZ3DwHzn30xISRq3h9Ges2xu4WxASL_apSqIGsgf8dIdqpYLJpiAdkB6XUNBYWi4wCDmin2yw9uQqZtXwHcnvZZV3YLqQaKKtP4uvECwAleXRJNHsXnzyHTcm6dQSnXFSQ5p-FIgGPAZ7GNvNj1kelAIV9mA40Q6UMiglfYme1aTy2PA7VxNY6fDyeHkGxGkfFufEfhSnE6uR2fPlKE3u1luC7M_Wng0qoMZxraPv5btG5nu6eB3JrIcle4A8RcRnGE11xGPsTmHZ3ePYAME7gVuag6EWkT8eFVsg7ErYfDS9nrO7bRZGrOnFZFt-XFFP3dw7Y6YgwVjlMVAdRxe8jgLOFOkgl7JPr7jkRfppPZu6FnBCfM48rPkmUA6C7dFnIjDGcPcm1dQQKGOmplyhQoPjVszAbSJR-UwDKQ3XXoxe32Kbo6SbLRfm8br797ywqordkFgvHAOqO7gWBIBtt9l4tkoRmCtrFjRAvaikCvuy1gNPE01HDvJ-Nph8WP1Q-BmLlIZuOHWT047BEXCdjcILCEuqXX_OCHjuaqV9NHcmf2Np4ideXcjKPyHuFx2F_DoU21o1vaSnn2kdb6-045GZovQrKCsX_5dzaEao-ZZ38a5aWlbWqzbnlb6O8HlAtEgqyT4kZvh7KSeyOqUPUKOSs13dHz1h2GKm6AnD8I7AlVTgxeD4IaEgEPDVnSuMb29tzSNriu4gb-oq2ecRc5-vXwgnnicdjQpkg4lErgZdBUF-BpdNIsGmCQjrsIeDtEfRbdhSMTR78IvQguDtDge2yaHzRVW1uecM9iSnkJihHG-8eB92B7gafNOnizDN5L8ZR3L8q9pKB1ZRJE81mVtLHYolYOqmOov72sAeLFUpYKNyT5wIrjFMEicyvJYPF9-TLpmSge0eh5yuhuADJfB_XMCqXoXo9bK8NsGlJtP-UOLReCm30lF4tALzvDHMqGkWpzRLZEBa906KI7fgbdutO3eEDw7x4CElSVyy5hQbbsg04YVpLGjsKpIZmrEhLCqillpf1WFE1Zaz7wxdX1IpSwKmuIOMLzwKqhoPXGtm2TcOiGrWuNzAtjStxHdsoDWXEKt43x3KDVS-5Zh72IXvOx8qJTeFsGXSX9ZNnziouk1w1Vy3CKJj-UcfhuhudEQ1JcWrOpkO1XGcEkkH0lSm3xgArYLwViad2QWm4C0tFSKuXF54GDIArP3nPvEOsWQOP5-LFjEXdXj7pUuXd_I2_97YrPXZ31FEuHJV6c9897zaCEJO2CE3K6AU1wT3mtoyECTEMYmc2OpmdUpUEq7sdbGrsR5OC8NxubWVsRz8B2i1jY7ngovVGEioN_Ri_teweU2rFLEir7suahc-43nwCqtCdUgE1DTTdcdqjOYykuGMkCWQcpF3z8LaaMlodz9o632Z0KPKVVyFhescd6BihMOlzX6GWhvKOTEssGm0C5adDNFG8Gq5DQuiNP_U9-KkaEUY2rQ8uew54L2JVWLhx2HVCv6YT88gMJMxOju0fjSJ3fA1Yc4hRhEy3XXndhW4bWNZ_sZS9TRzwWDYpdkHDQ5R44dK-FH6gMnhceHm_YaVwOcRtCIZxLI6DFFnRsBlFlH1rCEs9b00HDYgfisATpT4etAXK5vTEYN83WHKcds8GdH7KQLoJLhkjWfFQBuHO9UrAAnH7FIWfjoYd4WHOfg7h42rcNEciXHNixL617Bzr-UKTjbmF8W1V9HvRnc9yecwp9p-j47mATYk7PtIcPdz9hrlK2G3OvE1Ez6TXFoRwqBzA__yRGfAzk9nYMEp91mnkk7wZzcPatRDzFTxr9JVDKU_E59EeiJGdJ7GziMfS29F8c2i4JOt39bIOPnogNE7Cybtg8WEUM8Cy09GrO-hoyNOMUnqznLyfk9i6sm4vOH-6pxBTCm6V_NbryTV0oxFRw322hq5w3hXp1jo-7b3stTqEnLZ91Da1jYO0hsxw6RxKCpj07Z6H-PdCEal3qIsl1P4WsVUUoYvlQcdxNZULRVPm-ZRfhcHyqPdR44D4N0hniBXnOIgB3I3iLQeaGYgM1_ziZvmsvRne3j04Kcu4IBEjdcl6KNy7YNcPy9kq570N-5Adu6bsDnNXQ0m3xfpY8N_bvhuagYBbFDh5gIh5yrThg8M7ewdrWJEXgfnDBFsNeNT-n_yPoOf2fRPAFfv8PdOzz01Y0AAA',
            'style'     => 'H4sIAAAAAAAAAyXMSwqAIBAA0KsIbftuVVp6j8lGGLCZ0AmM6O4tegd4vuqdcYUxkuJxNXqiZCm2CyG4JKx2k7ybZT6bgUKQ-wpch4qFklNsOuwYpYCSsGVhdK-f_vMDjwn581sAAAA',
            'vanity'    => 'H4sIAAAAAAAAA22S227iMBCGX2VkbhdCD4uESaLVIqqqUgvq4aKXTmwSL8ZjjQcCb79OSm_ayhppRvZ8_z-2c1aVM1Ab52JQtfVNIaaiL4PS-lJWSNpQn0U-O1OIStW7hvDgtRzN5_NFZzW38vpmGk4LUeZMKTQclbONLwRj-Gy8QCVchRNcp_id4jZ1fUiMyTYty4jO6uHIaLlc9sTkzcOFsUXPskKnodcDRVa5X1H5OI6G7HZRo0OSo9lstkjKsvcUMFq26CUZp9geTWL-ybOeWuYZ62924ZI7s2UBX8zfJNVpWrcf0ypoyWwL0TIHmWVd100C4T9Tc4venAPyBKnJBNROxViI2rLZH05WlI-rx7-rZ1jfweZ5_bBavsL9-mn1Dpv1a56pMq_oR_rBJ-P7SY178Q35knbgXtHRRDYEG0JORtLo8GS4Q9r10GTvaLXRUJ3hbYANcsNFZP3jZcOvKP8DIZwO2B0CAAA',
        ]);
        $factory->setCallbackData([
            'emailCallback' => function($email, $style = null){
                $value = $email;
                $display = 'style="display:' . ['none',' none'][random_int(0,1)] . '"';
                $style = $style ?? random_int(0,5);
                $props[] = "href=\"mailto:$email\"";
        
                $wrap = function($value, $style) use($display){
                    switch($style){
                        case 2: return "<!-- $value -->";
                        case 4: return "<span $display>$value</span>";
                        case 5:
                            $id = 'r3ko';
                            return "<div id=\"$id\">$value</div>\n<script>document.getElementById('$id').innerHTML = '';</script>";
                        default: return $value;
                    }
                };
        
                switch($style){
                    case 0: $value = ''; break;
                    case 3: $value = $wrap($email, 2); break;
                    case 1: $props[] = $display; break;
                }
        
                $props = implode(' ', $props);
                $link = "<a $props>$value</a>";
        
                return $wrap($link, $style);
            }
        ]);

        $transcriber = new DataTranscriber($templateData, $factory);

        $template = new ArrayTemplate([
            'doctype',
            'injDocType',
            'head1',
            'injHead1HTMLMsg',
            'robots',
            'injRobotHTMLMsg',
            'nocollect',
            'injNoCollectHTMLMsg',
            'head2',
            'injHead2HTMLMsg',
            'top',
            'injTopHTMLMsg',
            'actMsg',
            'errMsg',
            'customMsg',
            'legal',
            'injLegalHTMLMsg',
            'altLegalMsg',
            'emailCallback',
            'injEmailHTMLMsg',
            'style',
            'injStyleHTMLMsg',
            'vanity',
            'injVanityHTMLMsg',
            'altVanityMsg',
            'bottom',
            'injBottomHTMLMsg',
        ]);

        $hp = new Script($client, $transcriber, $template, $templateData, $factory);
        $hp->handle($host, $port, $script);
    }

    public function handle($host, $port, $script)
    {
        $data = [
            'tag1' => 'a424633113682ca8e4d45ba885094829',
            'tag2' => 'eca2030e980b8d0ddfb1c2b0f4a65989',
            'tag3' => '3649d4e9bcfd3422fb4f9d22ae0a2a91',
            'tag4' => md5_file(__FILE__),
            'version' => "php-".phpversion(),
            'ip'      => $_SERVER['REMOTE_ADDR'],
            'svrn'    => $_SERVER['SERVER_NAME'],
            'svp'     => $_SERVER['SERVER_PORT'],
            'sn'      => $_SERVER['SCRIPT_NAME']     ?? '',
            'svip'    => $_SERVER['SERVER_ADDR']     ?? '',
            'rquri'   => $_SERVER['REQUEST_URI']     ?? '',
            'phpself' => $_SERVER['PHP_SELF']        ?? '',
            'ref'     => $_SERVER['HTTP_REFERER']    ?? '',
            'uagnt'   => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        $headers = [
            "User-Agent: PHPot {$data['tag2']}",
            "Content-Type: application/x-www-form-urlencoded",
            "Cache-Control: no-store, no-cache",
            "Accept: */*",
            "Pragma: no-cache",
        ];

        $subResponse = $this->client->request("POST", "http://$host:$port/$script", $headers, $data);
        $data = $this->transcriber->transcribe($subResponse->getLines());
        $response = new TextResponse($this->template->render($data));

        $this->serve($response);
    }

    public function serve(Response $response)
    {
        header("Cache-Control: no-store, no-cache");
        header("Pragma: no-cache");

        print $response->getBody();
    }
}

Script::run(__REQUEST_HOST, __REQUEST_PORT, __REQUEST_SCRIPT, __DIR__ . '/phpot_settings.php');

