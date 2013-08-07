# captain's log parser

## what is this?

I keep track of what I do every day, and I do that in text files. One for every day. Until recently, this has been good enough. It's like a daily journal: a folder full of text files, each representing one day of work.

But how do I get data out of that? Thankfully, I keep a very standard (but somewhat flexible) format to my text files.

This is a parser for those text files. The parser goes through a given directory, finds text files, and parses them into one big data object if the file is of the right format. That format is outlined below.

## parser usage

Simply run `./parser.php name_of_directory/` where that directory path name is where your text files are stored. That's it!

The parser does not modify your files in any way, it just reads them.

## overall format considerations

save the log file as a plain text .txt file, any name, whatever. my convention is to have the filename be the date, in the same form as the first line, i.e. 2013-08-07.txt, but you can do whatever.

the parser will ignore any file that isn't .txt, and will ignore any .txt file that doesn't have the date as the first line. any spaces at the beginning or ends of lines will be parsed out.

if there are files that duplicate dates (i.e. you have two text files that start with 2013-04-12 on the first line), the parser will throw an error.

## the file format spec

first line: the day's date in format: YYYY-MM-DD

third line: "Worked on..." or "Worked on:" or "Worked on" (case insensitive)

lines 5-n: a list of lines, each line being an item you worked on that day

for each of those items, each on a single line, you can include the following:

- if you put a dash (-) and then a space at the start of the line, the parser will ignore it. it just makes the file easier to read for you.
- the time spent doing the thing, in brackets, for time tracking. examples: [1 hour] or [1h30m] or [9:00am - 1:00pm] or [9am to 11am]
- a footprints ticket ID or project ID associated with it, enclosed in curly braces, the first number being the workspace and the second number being the ticket number i.e. {1#6112} is Help Desk workspace ticket ID 6112
- if you include "meeting" in the line item, it'll count as a meeting

when you're done listing out what you've done, add a blank new line

line n+2: "Notes..." or "Notes:" or "Notes" (case insensitive) (optional, you don't need to have a notes section at all.) 

lines n+4 and beyond: whatever additional notes you want to add about the day. if you write just "welp" or "welp." that means you have no notes. (optional, you don't need to have this at all, you can leave it blank.)

## example

    2013-08-07
    
    Worked on:
    
    - change management meeting [19 minutes]
    - footprints user group meeting [9:19am to 10am]
    - fixed median bug that caused a weird error [15 mins] {1#5342}
    - talked to andrew about some important project
    
    notes
    
    today I was really bored.

## what you get at the end

when it's all done parsing, you will have a massive PHP array of everything you did, how much time it took per item, what tickets/workspaces were involved, how much time you spent working per day, and how much time you spent working over all of the logs, as well as how many meetings you attended per day or of all time.

basically it makes something that's easily storable in a database or exported to JSON/XML or reported on for metrics. or it's just neat info to have.