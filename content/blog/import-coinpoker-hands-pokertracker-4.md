---
title: "How to Import CoinPoker Hands into PokerTracker 4 (Step by Step)"
slug: import-coinpoker-hands-pokertracker-4
excerpt: "CoinPoker hands go into PokerTracker 4 by hand, after your session. Here's the exact import route, files vs directory, and what to check if hands go missing."
meta_title: "Import CoinPoker Hands into PokerTracker 4 — Step by Step"
meta_description: "How to import your CoinPoker hand history into PokerTracker 4: Get Hands From Disk, files vs directory, and what to do when hands don't import."
published: true
---

## Before you import

You need the hand history file first. CoinPoker doesn't write real-time hand history files to your computer, so PokerTracker 4 can't pick hands up on its own while you play. You request the download from PokerIntel, CoinPoker emails you a link, and you import the file afterwards. The request itself is covered in [how to export your hand history from CoinPoker](/blog/how-to-export-hand-history-coinpoker).

If the file arrives compressed, extract it before importing.

## Step by step

PokerTracker 4's own [CoinPoker guide](https://docs.pokertracker.com/pt4/site-configuration/coinpoker-configuration-guide/) describes the manual route:

1. Open PokerTracker 4 and go to the **Play Poker** tab.
2. Click **Get Hands From Disk**.
3. Choose **Select Files** to import individual files, or **Select Directory** to import every file in a folder and its subfolders.
4. Pick the file or folder and confirm, then let the import finish.

Menu names can shift between versions of the software, so look for the same wording if your screen differs slightly.

## Files or directory?

- **Select Files** is the simple choice for a single export.
- **Select Directory** is better once you keep all your CoinPoker exports in one folder. Point PokerTracker at the folder each time and it reads everything inside, subfolders included.

Cash games and tournaments are downloaded separately from CoinPoker, so you'll import at least two files if you play both. Keeping them in clearly named folders makes it obvious what you've already brought in.

## Don't re-import the same hands

After your first full download, request only the new date range each time. Smaller files import faster and you avoid feeding the tracker hands it already has.

## If hands are missing or rejected

- **Nothing imports.** Open the file in a text editor. It should be plain text, with hands that start with `CoinPoker Hand #`. If it's an archive or looks empty, the date range probably had no hands for that game type.
- **Errors on some hands.** An "Invalid pot size" message on many hands usually has one specific cause, explained in [fixing "Invalid Pot Size" errors on CoinPoker imports](/blog/fix-invalid-pot-size-error-coinpoker-import).
- **Hands import but the numbers look off.** CoinPoker's format differs from the PokerStars-style layout trackers are built around: the header, the ₮ currency sign, the player lines and the timestamps. [PokerHandsConverter](/) rewrites the file into a format PokerTracker 4 reads, in a few seconds, before you import.

## What you won't get

Because there is no live feed, there is no real-time HUD for CoinPoker, and PokerTracker 4 won't import the hands automatically. The workflow is: play, request, import, review. If you haven't chosen a tracker yet, [PokerTracker 4 vs Hold'em Manager 3 for CoinPoker](/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) compares the two.
