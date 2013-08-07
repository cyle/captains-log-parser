# captain's log format

## overall

save this as a plain text .txt file, any name, whatever. my convention is to have the filename be the date, in the same form as the first line, i.e. 2013-08-07.txt

the parser will ignore any file that isn't .txt, and will ignore any .txt file that doesn't have the date as the first line.

if there are files that duplicate dates (i.e. you have two text files that start with 2013-04-12), the parser will throw an error.

## the file format spec

first line: the day's date, format: YYYY-MM-DD

third line: "Worked on..." or "Worked on:" or "Worked on" (case insensitive)

lines 5-n: a list of lines, each line being an item you worked on that day

for each of those items, each on a single line, you can include the following:

- if you put a dash (-) and then a space, the parser will ignore it.
- the time spent doing the thing in brackets, i.e. [1 hour] or [1h30m] or [9:00am - 1:00pm] or [9am to 11am]
- a footprints ticket ID or project ID associated with it, enclosed in curly braces, the first number being the workspace and the second number being the ticket number i.e. {1#6112} is Help Desk workspace ticket ID 6112
- if you include "meeting" in the line item, it'll count as a meeting

when you're done listing out what you've done, add a blank new line

line n+2: "Notes..." or "Notes:" or "Notes" (case insensitive) (optional, you don't need to have this.) 

lines n+4 and beyond: whatever additional notes you want to add about the day. if you write just "welp" or "welp." that means you have no notes. (optional, you don't need to have this at all, you can leave it blank.)

## example

    2013-08-07
    
    Worked on:
    
    - change management meeting [19 minutes]
    - footprints user group meeting [9:19am to 10am]
    - fixed median bug that caused a weird error [15 mins] {1#5342}
    - talked to andrew about some important project
    
    notes
    
    today i was really bored.
    