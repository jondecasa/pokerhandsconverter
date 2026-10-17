---
title: "How to Import CoinPoker Hands into Hold'em Manager 3"
slug: import-coinpoker-hands-holdem-manager-3
excerpt: "Hold'em Manager 3 imports CoinPoker hands from files, not live. Here's the File menu route, the ZIP option, and why hands sometimes get skipped."
meta_title: "Import CoinPoker Hands into Hold'em Manager 3"
meta_description: "Step-by-step: import your CoinPoker hand history into Hold'em Manager 3 with File > Import Files or Import Folders, plus ZIP files and re-imports."
published: true
published_at: "2026-10-23T09:00:00+00:00"
---

## Before you import

First get the file out of CoinPoker. CoinPoker doesn't save hand histories on your computer as you play. You request them from PokerIntel and receive a download link by email, as described in [how to export your hand history from CoinPoker](/blog/how-to-export-hand-history-coinpoker). Hold'em Manager 3 can't read hands live from CoinPoker, so this is a manual, after-the-session import.

As far as we can tell from Hold'em Manager's documentation, there is no automatic CoinPoker import, so the file route below is the way to do it.

## Step by step

Hold'em Manager's knowledge base describes [importing hands](https://kb.holdemmanager.com/knowledge-base/article/importing-hands) from the **File** menu:

1. Open Hold'em Manager 3.
2. Open the **File** menu.
3. Choose **Import Files** to pick individual hand history files, or **Import Folders** to pick a folder.
4. Select what you want in the window that opens and confirm.
5. Wait for the import to finish.

Menu wording can change between releases, so check the current knowledge base if a name looks different.

## ZIP files

Hold'em Manager 3 can import hand histories directly from `.zip` files, according to its documentation. If your CoinPoker download arrives as an archive, you may be able to import it as is. If it doesn't work, extract it first and import the folder.

## Hands that don't show up

- **They were already in the database.** Hold'em Manager skips hands it already has. If you want to import the same files again, support posts point to a setting under **Tools → Settings → Import** called **Allow reimport of hand histories**. Turn it on, import, then think about turning it off again.
- **The date range had no hands.** An empty or tiny file usually means the range or game type had no hands. Cash games and tournaments are downloaded separately from CoinPoker.
- **Odd numbers or errors.** CoinPoker's format differs from the PokerStars-style layout trackers expect, in the header, the ₮ sign, the player lines and the timestamps. [PokerHandsConverter](/) rewrites the file before you import. If you saw an "Invalid pot size" message, see [this fix](/blog/fix-invalid-pot-size-error-coinpoker-import).

## Same job in PokerTracker 4

If you use more than one tracker, the equivalent route in PokerTracker is described in [how to import CoinPoker hands into PokerTracker 4](/blog/import-coinpoker-hands-pokertracker-4). And if you're still choosing, [PokerTracker 4 vs Hold'em Manager 3 for CoinPoker](/blog/pokertracker-4-vs-holdem-manager-3-coinpoker) compares them.
