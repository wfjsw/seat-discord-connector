<?php
/**
 * This file is part of slackbot and provide user synchronization between both SeAT and a Slack Team
 *
 * Copyright (C) 2016, 2017, 2018  Loïc Leuilliot <loic.leuilliot@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Warlof\Seat\Connector\Discord\Http\Controllers;

use Seat\Web\Http\Controllers\Controller;
use Warlof\Seat\Connector\Discord\Models\DiscordUser;
use Yajra\Datatables\Facades\Datatables;

class DiscordController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function getUsers()
    {
        return view('discord-connector::users.list');
    }

    /**
     * @return \Illuminate\Http\RedirectResponse
     */
    public function postRemoveUserMapping()
    {
        $slackId = request()->input('slack_id');

        if ($slackId != '') {

            if (($slackUser = DiscordUser::where('slack_id', $slackId)->first()) != null) {
                $slackUser->delete();

                return redirect()->back()->with('success', 'System successfully remove the mapping between SeAT (' .
                    $slackUser->user->name . ') and Slack (' . $slackUser->name . ').');
            }

            return redirect()->back()->with('error', sprintf(
                'System cannot find any suitable mapping for Slack (%s).', $slackId));
        }

        return redirect()->back('error', 'An error occurred while processing the request.');
    }

    /**
     * @return mixed
     * @throws \Seat\Services\Exceptions\SettingException
     * @throws \Warlof\Seat\Slackbot\Exceptions\DiscordSettingException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidConfigurationException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\InvalidContainerDataException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\SlackScopeAccessDeniedException
     * @throws \Warlof\Seat\Slackbot\Repositories\Slack\Exceptions\UriDataMissingException
     */
    public function getUsersData()
    {
        if (is_null(setting('warlof.discord-connector.credentials.bot_token', true)))
            return Datatables::of(collect([]))->make(true);

        $users = DiscordUser::all();

        return Datatables::of($users)
            ->addColumn('group_id', function($row){
                return $row->group_id;
            })
            ->addColumn('user_id', function($row){
                return $row->group->main_character_id;
            })
            ->addColumn('user_name', function($row){
                return optional($row->group->main_character)->name ?: 'Unknown Character';
            })
            ->editColumn('discord_id', function($row){
                return (string) $row->discord_id;
            })
            ->addColumn('discord_nick', function($row){
                return $row->nick;
            })
            ->make(true);
    }

}
