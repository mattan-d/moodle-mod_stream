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
$string['defaultautoplaynext'] = 'ניגון אוטומטי של הסרטון הבא כברירת מחדל';
$string['defaultautoplaynext_desc'] = 'הגדר את ברירת המחדל עבור "ניגון אוטומטי של הסרטון הבא" בעת יצירת פעילויות Stream חדשות. כאשר האפשרות מופעלת, סרטוני הפלייליסט יתנגנו ברצף.';
$string['identifier_help'] = 'מזהי הווידאו משרת ה-Stream.';
$string['loadind'] = 'טוען רשימת וידאו...';
$string['modulename'] = 'STREAM';
$string['modulename_help'] =
        'Stream היא פלטפורמת וידאו חדשנית המיועדת לבתי ספר, ומשתלבת בצורה חלקה עם מערכות קיימות להוראה מיטבית. תוסף Moodle זה מביא את יכולות Stream המתקדמות ל-LMS שלך, ומשדרג את חוויית הלמידה עבור מורים ותלמידים כאחד.';
$string['autoplaynext'] = 'ניגון אוטומטי של הסרטון הבא';
$string['autoplaynext_desc'] = 'הפעל אוטומטית את הסרטון הבא בפלייליסט בסיום הסרטון הנוכחי';
$string['autoplaynext_help'] = 'כאשר האפשרות פעילה, בסיום סרטון בפלייליסט יופעל אוטומטית הסרטון הבא. כאשר האפשרות כבויה, יש לבחור ידנית את הסרטון הבא.';
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
$string['courseid'] = 'מזהה קורס';
$string['courseid_help'] = 'אופציונלי. הזן מזהה קורס כדי לעבד רק פעילויות Stream בקורס זה. השאר ריק כדי לעבד את כל הקורסים.';
$string['courseids'] = 'קורסים';
$string['courseids_help'] = 'חפש ובחר קורס אחד או יותר לעיבוד. ייכללו רק פעילויות Stream בקורסים שנבחרו.';
$string['coursenotfound'] = 'קורס עם מזהה {$a} לא נמצא.';
$string['coursescope'] = 'היקף קורסים';
$string['coursescope_help'] = 'בחר האם לעבד פעילויות Stream בכל הקורסים במערכת, או רק בקורסים ספציפיים שייבחרו למטה.';
$string['coursesselectionrequired'] = 'יש לבחור לפחות קורס אחד.';
$string['allcourses'] = 'כל הקורסים';
$string['selectedcourses'] = 'קורסים נבחרים';
$string['searchcourses'] = 'חיפוש קורסים...';
$string['deletegradeitems'] = 'STREAM: מחיקת פריטי ציון';
$string['deletegradeitems_desc'] = 'איתור פעילויות Stream עם פריטי ציון ומחיקתם מיומן הציונים. הפעולה גם מאפסת את הגדרת הציון בכל פעילות ל-0 ומחשבת מחדש את יומן הציונים בכל קורס מושפע. זמין למנהלי מערכת בלבד.';
$string['deletegradeitems_deleted'] = 'נמחק פריט ציון מספר {$a}';
$string['deletegradeitems_dryrunnotice'] = 'מצב הרצה יבשה — לא בוצעו שינויים.';
$string['deletegradeitems_error'] = 'שגיאה: {$a}';
$string['deletegradeitems_link'] = 'פתיחת כלי מחיקת פריטי ציון';
$string['deletegradeitems_nonefound'] = 'לא נמצאו פעילויות Stream עם פריטי ציון.';
$string['deletegradeitems_scope_all'] = 'היקף: כל הקורסים';
$string['deletegradeitems_scope_selected'] = 'היקף: {$a} קורס/ים נבחרים';
$string['deletegradeitems_skipped'] = 'דולג — לא נמצא פריט ציון';
$string['deletegradeitems_summary'] = 'עובדו: {$a->processed}. נמחקו: {$a->deleted}. דולגו: {$a->skipped}. נכשלו: {$a->failed}.';
$string['deletegradeitems_woulddelete'] = 'יימחק פריט ציון מספר {$a}';
$string['deletegradeitems_regradesuccess'] = 'יומני הציונים חושבו מחדש בהצלחה עבור {$a} קורס/ים.';
$string['deletegradeitems_regradesummary'] = 'חישוב מחדש של יומני ציונים הסתיים. הצליחו: {$a->success}. נכשלו: {$a->failed}.';
$string['deletegradeitems_regradeerror'] = 'נכשל חישוב מחדש של יומן הציונים לקורס {$a->courseid}: {$a->error}';
$string['dryrun'] = 'הרצה יבשה';
$string['dryrun_help'] = 'כאשר מופעל, הכלי מציג בלבד מה יימחק ללא ביצוע שינויים. כבה אפשרות זו כדי לבצע מחיקה בפועל.';
$string['run'] = 'הפעלה';
$string['tools'] = 'כלים';
$string['tools_desc'] = 'כלי תחזוקה ניהוליים עבור Stream.';
