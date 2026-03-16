# Stream Moodle Plugin

![Stream Logo](https://support.centricapp.co.il/static/centricapp-wordmark-tight.svg)


[![Maintained by Mattan Dor (CentricApp)](https://img.shields.io/badge/Maintained%20by-Mattan%20Dor%20(CentricApp)-brightgreen)](https://centricapp.co.il)


Stream is a revolutionary video platform tailored for academic institutions, offering seamless integration into existing systems and unprecedented pedagogical optimization. This Moodle plugin facilitates the incorporation of Stream's powerful features directly into your learning management system, enhancing the educational experience for both instructors and students.

## Explanation for non‑technical users / managers

### What is the Stream activity?
The Stream activity lets you show course videos from the Stream platform directly inside a Moodle course. Students and teachers can watch the videos in Moodle without leaving the course page, using a simple playlist and built‑in player.

### What the plugin can do
- **Central place for course videos**  
  - Shows a playlist of all relevant course recordings and uploaded videos.  
  - Can automatically collect new recordings (for example, from connected systems like Zoom, Webex, or Teams, depending on how Stream is configured).

- **Easy viewing experience for students**  
  - Students open the activity and see a clear list of videos for the course.  
  - Videos are played in an embedded player inside Moodle, with audio options where enabled.

- **Support for audio‑focused courses**  
  - For certain courses (for example, music or language learning), the plugin can automatically show a built‑in audio player view, making audio‑only content easier to use.

- **Flexible control for teachers and admins**  
  - Choose which videos appear in the activity (manually select) or let the system collect them automatically.  
  - Decide the order of videos in the playlist by simple drag‑and‑drop.  
  - Control when the activity opens and closes for students (availability dates).

- **Data and safety**  
  - The plugin can send basic user details (name, email, student ID) to Stream to support security, analytics, and anti‑piracy features configured on the Stream side.

### How a teacher uses the Stream activity
1. **Add a new Stream activity**  
   - In a Moodle course, turn editing on.  
   - Click **“Add an activity or resource”**.  
   - Choose **“STREAM”** from the list and click **Add**.

2. **Basic settings**  
   - Enter a **Title** (for example, “Lecture Videos” or “Unit 1 – Video Lessons”).  
   - Optionally add a short description so students know what they will find inside.

3. **Choose which videos to show**  
   - If automatic collection is enabled by your admin, you can turn on **“Video Recordings Collection Mode”** to let the system pull in all relevant course recordings.  
   - You can also **manually search and select videos** from the Stream server and add them to the playlist.

4. **Arrange the playlist**  
   - Use **drag‑and‑drop** to change the order of videos (for example, by date or by topic).  
   - Save changes so students see the playlist in the order you defined.

5. **Audio options (if enabled by your institution)**  
   - For courses that should use an audio‑only player, your site admin can configure which courses get the built‑in audio player automatically.  
   - As a teacher, you may see an option like **“Include Audio”** to decide whether audio is enabled in the player for this activity.

6. **Control when students can access it**  
   - In the **Timing** section, you can set an **Open time** (from when students can see/watch) and a **Close time** (until when it is available).  
   - If you leave these disabled, students can access the activity at any time.

7. **Save and display**  
   - Click **Save and display** to see how the activity looks.  
   - Students will now see a Stream activity in the course. When they click it, they get a clean video (or audio) viewing page with the playlist you configured.

### What managers / admins should know
- **Connection to Stream**  
  - The plugin must be connected to your organization’s Stream server (API endpoint, account ID, identifiers, etc.). This is done once by a Moodle/IT admin in the plugin’s settings screen.  
  - There is a **connection status** check in the settings to confirm that Moodle can successfully talk to the Stream system.

- **Default behavior and automation**  
  - Admins can define default settings (for example, whether audio is included by default, which courses get the audio‑only interface, and how automatic video collection behaves).  
  - This allows consistent behavior across courses without requiring teachers to understand technical details.

- **Privacy and data**  
  - The plugin shares limited user information (full name, email, student ID) with the Stream service when a user accesses a Stream activity, so the external system can provide statistics, personalization, and security.  
  - These details should be covered in your institution’s privacy and data‑processing documentation.

## Features

### Simplified Video Management
- Upload videos manually or import recordings from Zoom, Webex, or Teams effortlessly.
- Comprehensive video content management through one intuitive interface.

### Professional Editing and Transcription
- Professional editing capabilities enable users to create polished videos quickly.
- Automatic transcription available in 98 languages, enhancing accessibility for all users.

### User-Friendly Interface
- Tailored for academic institutions, ensuring seamless navigation for both staff and students.
- Easy video upload directly to the LMS with just one click.

### Comprehensive Pedagogical Experience
- Manage users, edit recordings, and facilitate collaboration through dedicated channels.
- Seamless integration with any LMS via the LTI protocol, transforming the platform into a vital educational tool.

### Customization and Branding
- Flexible interface customization to align with your institution's branding and layout preferences.
- Create a digital learning space tailored precisely to your organization's character.

### Data-Driven Insights
- Access shared statistical data and insights for informed decision-making and continuous improvement.
- Real-time engagement metrics empower staff to design teaching plans based on user interaction.

## Get Started
To integrate Stream seamlessly into your Moodle platform and explore its full potential, schedule a consultation with our team. We are committed to understanding your organization's unique needs and providing personalized assistance.

For further details and inquiries, feel free to [contact us](https://stream-platform.cloud/en/) via our contact form or email.

Branches
--------
The following git branches are supported:

| Moodle version     | Branch            |
|--------------------|-------------------|
| Moodle 3.4 to 4.1  | MOODLE_401 |
| Moodle 4.2         | MOODLE_402 |

**Stream © 2024**
