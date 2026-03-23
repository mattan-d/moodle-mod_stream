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
 * Strings for component 'mod_stream', language 'he'.
 *
 * @package    mod_stream
 * @copyright  2024 mattandor <mattan@centricapp.co.il>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['accountid'] = 'מזהה חשבון Stream';
$string['accountid_desc'] = '';
$string['apiendpoint'] = 'כתובת קצה API של Stream';
$string['apiendpoint_desc'] = 'בחר את כתובת ה-API של Stream שבה תשתמש פעילות Stream להתחברות.';
$string['apiidentifier'] = 'מזהה API של Stream';
$string['apiidentifier_desc'] = 'שדה המזהה לשימוש בעת קריאה ל-API של Stream';
$string['before'] = 'לפני';
$string['builtinaudioplayer'] = 'סוגי קורסים עם נגן שמע מובנה';
$string['builtinaudioplayer_desc'] = 'תבנית לזיהוי שמות קורסים קצרים באמצעות ביטויים רגולריים.';
$string['collectionmode'] = 'מצב איסוף הקלטות וידאו';
$string['collectionmode_desc'] = 'הפעל אפשרות זו לאיסוף אוטומטי של הקלטות חדשות. עדיין ניתן לבחור ידנית סרטונים ספציפיים להתאמה אישית של רשימת ההשמעה.';
$string['collectionmode_help'] = 'כאשר מופעל, פעילות זו יכולה לאסוף אוטומטית הקלטות וידאו חדשות. ניתן גם לבחור ידנית סרטונים ספציפיים ליצירת רשימת השמעה מותאמת אישית. אם לא נעשתה בחירה ידנית, כל הקלטות הקורס ייכללו אוטומטית. תכונה זו פועלת בצורה הטובה ביותר עם תוסף local_stream מותקן ומוגדר.';
$string['connectionfailed'] = 'החיבור נכשל: ';
$string['connectionok'] = 'החיבור פעיל.';
$string['connectionsettings'] = 'הגדרות חיבור';
$string['connectionsettings_desc'] = 'הגדרות אלו מגדירות כיצד Moodle מתחבר ל-Stream.';
$string['connectionstatus'] = 'סטטוס חיבור';
$string['identifier'] = 'מזהי וידאו';
$string['defaultincludeaudio'] = 'כלול שמע כברירת מחדל';
$string['defaultincludeaudio_desc'] =
        'הגדר את הערך המוגדר כברירת מחדל עבור תיבת הסימון "כלול שמע" בעת יצירת פעילויות Stream חדשות. כאשר האפשרות מופעלת, פעילויות חדשות יכללו שמע כברירת מחדל.';
$string['identifier_help'] = 'מזהי הווידאו משרת ה-Stream.';
$string['loadind'] = 'טוען רשימת וידאו...';
$string['modulename'] = 'STREAM';
$string['modulename_help'] =
        'Stream היא פלטפורמת וידאו חדשנית המיועדת לבתי ספר, ומשתלבת בצורה חלקה עם מערכות קיימות להוראה מיטבית. תוסף Moodle זה מביא את יכולות Stream המתקדמות ל-LMS שלך, ומשדרג את חוויית הלמידה עבור מורים ותלמידים כאחד.';
$string['includeaudio'] = 'הכללת שמע';
$string['includeaudio_desc'] = 'אפשר שמע בנגן הווידאו';
$string['includeaudio_help'] = 'כאשר האפשרות פעילה, נגן הווידאו יכלול השמעת שמע. כאשר האפשרות כבויה, הסרטונים יתנגנו ללא קול.';
$string['audioplayer_idnumbers'] = 'קורסים עם נגן שמע מובנה';
$string['audioplayer_idnumbers_desc'] = 'הזן מחרוזות זיהוי (אחת בכל שורה) לזיהוי קורסים. אם שם הקורס הקצר (Short name) מכיל אחת מהמחרוזות הללו, נגן השמע יופעל אוטומטית. לדוגמה: "-H" יתאים לקורס "Test-H".';
$string['modulenameplural'] = 'STREAM';
$string['nametitle'] = 'כותרת';
$string['noresults'] = 'לא נמצאו תוצאות או סרטונים.';
$string['noresultswithid'] = 'לא נמצאו תוצאות או סרטונים. מזהה/י וידאו שלא נמצאו: {$a}';
$string['pluginadministration'] = 'ניהול Stream';
$string['pluginname'] = 'STREAM';
$string['privacy:metadata'] =
        'כאשר STREAM משולב עם <a href="https://stream-platform.cloud">CentricApp</a> המערכת תשמור נתונים לזיהוי פיראטיות.';
$string['privacy:metadata:stream'] = 'הגדרות Stream';
$string['privacy:metadata:stream:email'] = 'כתובת האימייל של המשתמש שניגש לשרת Stream.';
$string['privacy:metadata:stream:fullname'] = 'השם המלא של המשתמש שניגש לשרת Stream.';
$string['safetyid'] = 'תעודת זהות תלמיד';
$string['safetynone'] = 'כל דבר';
$string['search'] = 'חיפוש';
$string['sortbyname'] = 'מיין לפי שם';
$string['sortbytimecreated'] = 'מיין לפי תאריך';
$string['sortbyview'] = 'מיין לפי צפיות';
$string['sortbysize'] = 'מיין לפי גודל';
$string['stream'] = 'STREAM';
$string['stream:addinstance'] = 'הוסף STREAM חדש';
$string['stream:view'] = 'צפה ב-STREAM';
$string['topic'] = 'נושא הווידאו';
$string['topic_help'] = 'נושא הווידאו משרת ה-Stream.';
$string['upload'] = 'העלה';
$string['views'] = 'צפיות';
$string['viewed'] = 'נצפה';
$string['playlistorder'] = 'סדר רשימת ההשמעה';
$string['playlistorder_help'] = 'גרור ושחרר סרטונים כדי לשנות את סדרם ברשימת ההשמעה.';
$string['selectedvideos'] = 'סרטונים שנבחרו';
$string['dragtoorder'] = 'גרור לשינוי סדר';
$string['activityclosed'] = 'פעילות זו נסגרה בתאריך {$a}';
$string['activitynotavailableyet'] = 'פעילות זו תהיה זמינה מתאריך {$a}';
$string['timeclose'] = 'זמן סגירה';
$string['timeclose_help'] = 'תלמידים לא יוכלו לצפות בפעילות לאחר זמן זה. אם מושבת, הפעילות תישאר זמינה بدون הגבלה.';
$string['timeopen'] = 'זמן פתיחה';
$string['timeopen_help'] = 'תלמידים יוכלו לצפות בפעילות רק החל מזמן זה ואילך. אם מושבת, הפעילות תהיה זמינה מיד.';
$string['timing'] = 'תזמון';
