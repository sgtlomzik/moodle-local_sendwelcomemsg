<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for local_sendwelcomemsg.
 *
 * @package    local_sendwelcomemsg
 * @copyright  2026 SgtLomzik <lomzike@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Serve files belonging to this plugin.
 *
 * The template attachments are read server-side when a message is composed and are
 * never meant to be downloadable over the web, so every request is refused.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context.
 * @param string $filearea The file area.
 * @param array $args The remaining path arguments.
 * @param bool $forcedownload Whether the file should be downloaded rather than shown.
 * @param array $options Additional options affecting the file serving.
 * @return bool Always false: nothing in this plugin is served over pluginfile.
 */
function local_sendwelcomemsg_pluginfile($course, $cm, $context, $filearea, $args,
        $forcedownload, array $options = []) {
    return false;
}
