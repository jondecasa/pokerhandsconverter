---
title: "CoinPoker Hand History Timestamps: Which Time Zone Is It?"
slug: coinpoker-hand-history-timezone
excerpt: "CoinPoker stamps every hand with a European time zone, while trackers expect an Eastern Time stamp. Here's what that does to your sessions and reports."
meta_title: "CoinPoker Hand History Time Zone (CEST) and Your Tracker"
meta_description: "Which time zone CoinPoker uses in its hand history, how it differs from the PokerStars-style Eastern Time stamp, and what it means for your sessions."
published: true
published_at: "2026-10-19T09:00:00+00:00"
---

## What the timestamp looks like

Every CoinPoker hand starts with a header like this:

```
CoinPoker Hand #130114200045: NLH (₮0.01/₮0.02) 2026/09/09 12:01:21 CEST
```

The time at the end is **local server time with a zone label**. In the exports we've worked with, that label is `CEST`, Central European Summer Time. The summary lines at the bottom of each hand repeat it, for example `Game ended: 2026/09/09 12:45:44 CEST`.

Hands from the PokerStars family look different. Their header carries a second timestamp in brackets, in **Eastern Time**, for example `[2026/09/09 6:01:21 ET]`. CoinPoker's header has no such stamp, which is one of the differences trackers trip over.

## Why the time zone matters

Hand timestamps decide where a hand sits in your history. Anything that groups hands by time depends on them:

- **Sessions.** Trackers split sessions by gaps between hands. Consistent timestamps keep one evening as one session.
- **Reports by day or hour.** If the tracker reads the time as a different zone than the one it was written in, your best "hours" and "days" shift.
- **Date filters.** A range such as "last 7 days" can include or miss hands near midnight.
- **Mixed databases.** If you also play on a room that stamps hands in Eastern Time, comparing sessions across rooms gets confusing.

None of this changes your winnings or your stats like VPIP. It affects time-based views.

## Summer time changes the label

CEST is the summer label. When Europe's clocks go back in autumn the zone becomes CET, so a file that spans that date can contain both labels. Keep that in mind when you read hand times around the change, and when you request a custom date range.

## What you can do

- **Know which zone you're looking at.** CoinPoker times are European local time, not your own time zone and not Eastern Time.
- **Don't mix zones inside one database without noticing.** If time-based reports matter to you, keep CoinPoker in its own database.
- **Let the conversion add the Eastern Time stamp.** [PokerHandsConverter](/) can keep the original local time and add the Eastern Time stamp in brackets (the default), output a single Eastern Time stamp, or leave CoinPoker's time untouched. You pick per upload.

After you've sorted the time zone, the next step is getting the hands in. [How to import CoinPoker hands into PokerTracker 4](/blog/import-coinpoker-hands-pokertracker-4) walks through it.
