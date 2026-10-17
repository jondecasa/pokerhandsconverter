---
title: "How to Import CoinPoker Hands into Hand2Note"
slug: import-coinpoker-hands-hand2note
excerpt: "Hand2Note imports CoinPoker hands from files through its Database menu. Here's the route, what to prepare first, and the nickname gotcha to avoid."
meta_title: "Import CoinPoker Hands into Hand2Note — Step by Step"
meta_description: "How to import your CoinPoker hand history into Hand2Note with Database > Import from Folder or Files, why to extract archives first, and the Hero nickname tip."
published: true
published_at: "2026-10-29T09:00:00+00:00"
---

## Before you import

CoinPoker doesn't save hand histories on your computer while you play, so Hand2Note has nothing to read live. You request your hands from PokerIntel, CoinPoker emails you a link, and you import the file afterwards. [How to export your hand history from CoinPoker](/blog/how-to-export-hand-history-coinpoker) covers the request.

Hand2Note's help pages say archives must be **extracted** before they can be imported, so unzip the download into a folder first.

## Step by step

Hand2Note's [help pages](https://hand2note.com/Help/getting-started) describe importing a folder from the **Database** menu:

1. Open Hand2Note.
2. Open the **Database** menu.
3. Choose **Import from Folder** to import every file in a folder, or **Import from Files** for individual files.
4. Browse to the folder where your extracted CoinPoker files are and confirm.
5. Wait for the import to finish.

Menu names can differ slightly between versions of the program.

## The nickname gotcha

In a Two Plus Two thread about [importing CoinPoker .txt files into Hand2Note](https://forumserver.twoplustwo.com/301/hand2note/importing-txt-files-coinpoker-h2n-1841322/), users report that the hands come in under a nickname such as "Hero (Coin Poker)". Because of that, many players keep CoinPoker in its **own database** instead of mixing it with other rooms. That keeps your Hero stats separate and makes it easy to clear and re-import if something goes wrong.

## If hands don't appear

- **Create a new database and repeat.** Hand2Note's support advice for missing hands is to create a fresh database and import again.
- **Check the file.** An empty or tiny file usually means the date range had no hands for that game type. Cash games and tournaments download separately.
- **Odd numbers or errors.** CoinPoker's format differs from the layout trackers are built around, in the header, the ₮ sign, the player lines and the timestamps. [PokerHandsConverter](/) rewrites the file before you import it.

## Same job elsewhere

If you also use another tracker, the routes are in [PokerTracker 4](/blog/import-coinpoker-hands-pokertracker-4) and [Hold'em Manager 3](/blog/import-coinpoker-hands-holdem-manager-3). And if you're deciding between trackers, [Hand2Note vs PokerTracker 4](/blog/hand2note-vs-pokertracker-4) compares them.
