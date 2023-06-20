<h1 align="center">🪙 Dime</h1>

<p align="center">Calculate your cryptoasset taxes in the UK</p>

<p align="center">
    <img alt="Preview" src="/art/preview.png">
	<p align="center">
		<a href="https://github.com/osteel/dime/actions"><img alt="Build Status" src="https://github.com/osteel/dime/workflows/CI/badge.svg"></a>
		<a href="//packagist.org/packages/osteel/dime"><img alt="Latest Stable Version" src="https://poser.pugx.org/osteel/dime/v"></a>
		<a href="//packagist.org/packages/osteel/dime"><img alt="License" src="https://poser.pugx.org/osteel/dime/license"></a>
	</p>
</p>

## About

Dime is a free and open-source command-line tool written in [PHP](https://www.php.net/) to calculate your cryptoasset taxes in the UK.

It takes a spreadsheet of transactions as input and returns the corresponding tax figures per tax year.

Dime is primarily intended for people already familiar with the UK's [cryptoassets tax rules](https://www.gov.uk/hmrc-internal-manuals/cryptoassets-manual "Cryptoassets Manual") looking for a privacy-preserving way to complete their tax return.

Dime is a project by [Yannick Chenot](https://twitter.com/osteel "Yannick Chenot on Twitter") that is also the object of a [blog series](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-why "Building a PHP CLI tool using DDD and Event Sourcing: why?").

## Disclaimer

This program is Copyright (C) 2022 by Yannick Chenot.

This program is free software: you can redistribute it and/or modify it under the terms of the [GNU Affero General Public License](LICENSE.txt) as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the [GNU Affero General Public License](LICENSE.txt) for more details.

You should seek the advice of a professional accountant before using this program.

## Table of contents

* [Installation](#installation)
	* [Composer](#composer)
	* [PHAR (Linux / Unix / macOS)](#phar-linux--unix--macos)
* [Usage](#usage)
* [Spreadsheet format](#spreadsheet-format)
	* [Date](#date)
	* [Operation](#operation)
	* [Market value](#market-value)
	* [Sent asset](#sent-asset)
	* [Received asset](#received-asset)
	* [Fee](#fee)
	* [Income](#income)
	* [Summary](#summary)
	* [Special cases](#special-cases)
* [How it works](#how-it-works)
* [Maintenance](#maintenance)
	* [Upgrade](#upgrade)
	* [Delete](#delete)
* [Issue reporting](#issue-reporting)
* [Contributing](#contributing)

## Installation

> **Note**
> Dime requires PHP 8.2 and the [BCMath](https://www.php.net/manual/en/book.bc.php) extension.

### Composer

You can instal Dime via [Composer](https://getcomposer.org):

```
$ composer global require osteel/dime
```

Once the script is done, you should be able to run `dime` from anywhere to use the application.

If not, make sure the `~/.composer/vendor/bin` directory is in your system's `PATH`.

<details>
<summary>Show me how</summary>

If it's not already there, add the following line to your Bash configuration file (usually `~/.bash_profile`, `~/.bashrc`, `~/.zshrc`, etc.):

```
$ export PATH=~/.composer/vendor/bin:$PATH
```

If the file doesn't exist, create it.

Run the following command on the file you've just updated for the change to take effect:

```
$ source ~/.bash_profile
```
</details>

### PHAR (Linux / Unix / macOS)

<!-- phar -->
[Download the PHAR archive](https://github.com/osteel/dime/releases/download/v0.1/dime) from the [latest release](https://github.com/osteel/dime/releases/tag/v0.1).
<!-- /phar -->

You can use the application straight away:

```
$ php dime
```

But you may want to move it to a directory that is in your system's `PATH`:

```
$ chmod +x dime
$ mv dime /usr/local/bin/dime
```

You can now run `dime` from anywhere instead of `php dime`.

## Usage

You can display Dime's help menu by calling the executable without arguments:

```
$ dime
```

Here is a brief description of the main commands.

### Processing transactions

Pass your transaction spreadsheet to the `process` command:

```
$ dime process transactions.csv
```

It will validate and process each transaction and display the corresponding tax figures or report any errors.

> **Note**
> You will need to run this command every time you update the spreadsheet.

See the [Spreadsheet format](#spreadsheet-format) section to learn how to report your transactions.

### Reviewing a tax year

Once your transaction spreadsheet has been processed you can review the corresponding tax figures whenever you like without having to run the `process` command again.

To list the available tax years and pick one to review:

```
$ dime review
```

To review a specific tax year:

```
$ dime review 2015-2016
```

## Spreadsheet format

Here is a [sample CSV file](/tests/stubs/transactions/valid.csv) you can use as a starting point. It is recommended to read this entire section to ensure proper reporting, however.

Your spreadsheet must contain at least the following columns:

| Date | Operation | Market value | Sent asset | Sent quantity | Sent asset is non-fungible | Received asset | Received quantity | Received asset is non-fungible | Fee currency | Fee quantity | Fee market value | Income |
| ---- | --------- | ------------ | ---------- | ------------- | -------------------------- | -------------- | ----------------- | ------------------------------ | ------------ | ------------ | ---------------- | ------ |

Column names are not case-sensitive and columns can be in any order. Extra columns will be ignored by the program.

### Date

The expected date format is `DD/MM/YYYY` (e.g. `21/10/2015`).

You can also specify the time of the transaction, although Dime doesn't need it.

### Operation

Accepted values: `receive`, `send`, `swap`, and `transfer`.

Each operation requires different columns to have values (see detail below). You can also use the [sample CSV file](/tests/stubs/transactions/valid.csv) as a guide.

#### Receive

This operation is for transactions where you receive a cryptoasset in exchange for nothing. This happens when someone gifts you some crypto, for instance, or when you get or claim an airdrop.

`receive` transactions require values for the `Market value`, `Received asset`, and `Received quantity` columns.

#### Send

This operation is for transactions where you send a cryptoasset and do not receive another cryptoasset or some fiat currency in exchange. This happens when you gift someone some crypto, for instance, or when you pay for a product or service using a cryptoasset.

`send` transactions require values for the `Market value`, `Sent asset`, and `Sent quantity` columns.

#### Swap

This operation is for transactions where you exchange a cryptoasset (or some fiat currency) for another cryptoasset (or some fiat currency). This happens when you buy some crypto off an exchange, for instance, or when you sell some crypto, or you exchange a cryptoasset for another one.

In any case, at least one side of the transaction must be a cryptoasset.

`swap` transactions require values for the `Market value`, `Received asset`, `Received quantity`, `Sent asset`, and `Sent quantity` columns.

#### Transfer

This operation is for transactions where you transfer a cryptoasset from and to a wallet that you control. This happens when you withdraw some crypto from an exchange and send it to your hardware wallet, for instance.

`transfer` transactions require values for the `Sent asset` and `Sent quantity` columns.

### Market value

The market value of a transaction is its value expressed in pound sterling at the time of the transaction.

The market value is at the centre of Dime's calculation rules so it is crucial to report it correctly.

#### Rules

When the transaction includes a sent asset (and is not a transfer – transfers don't need a market value), you must use the sent asset's market value as the transaction's market value, _based on the reported sent quantity_ (see [Sent quantity](#sent-quantity) section below to learn how to report it correctly).

> **Note**
> Be particularly vigilant when selling an asset for some fiat currency. It is tempting to use the received fiat amount as the market value, but if a fee was taken, the actual market value is the received amount plus the fee's market value.

When the transaction does not include a sent asset (i.e. `receive` transactions), the transaction's market value is the received asset's market value.

#### How to find the market value

There are several ways to figure out a transaction's market value.

Keeping in mind that when a transaction includes a sent asset, its market value takes precedence:

* If the sent asset is a fiat amount, use that as the market value;
* If performing a transaction on an exchange, use the exchange's rate for the asset at the time of the transaction;
* If using a decentralised protocol, use the value reported by the corresponding blockchain's explorer (see the value between brackets in the _Tokens Transferred_ section [here](https://etherscan.io/tx/0x2c9310e04c01e1329973c205cc6f3d3a7be3237ed09b968faef7ed85d9dfea65), for instance);
* If none of the above applies, look up the asset on price-tracking websites such as [CoinMarketCap](https://coinmarketcap.com/) or [CoinGecko](https://www.coingecko.com/).

> **Note**
> If any of the above amounts are expressed in a foreign currency, use HMRC's [exchange rates](https://www.gov.uk/government/publications/hmrc-exchange-rates-for-2023-monthly "HMRC exchange rates for 2023: monthly") to convert them to pound sterling.

Sometimes, while the transaction includes a sent asset, its price isn't tracked anywhere. In that case, use the received asset's market value plus the fee's market value, if you paid for that fee.

In some cases, none of the transaction's asset prices are tracked anywhere. When that happens, you are supposed to figure out the assets' _fair market value_, although there is no clear guidance on how to do that.

### Sent asset

A sent asset must be specified for `send`, `swap`, and `transfer` transactions.

Here is the detail of each related column.

#### Sent asset

This is the sent asset's symbol, or ticker (e.g. `BTC` for Bitcoin or `GBP` for pound sterling).

If the asset is an NFT, you can use any string value you like, so long as it is unique in your spreadsheet. I usually go for the collection's ID followed by the item's number (e.g. [this CryptoPunk](https://opensea.io/assets/ethereum/0xb47e3cd837ddf8e4c57f05d70ab865de6e193bbb/1008) would be ID `0xb47e3cd837ddf8e4c57f05d70ab865de6e193bbb/1008`).

#### Sent quantity

The quantity sent. For NFTs, that would be `1`.

Be as precise as possible and report all available decimal places.

> **Note**
> When the sent asset and the [fee](#fee)'s currency are the same (e.g. GBP in both cases), _and the fee's quantity was deducted from the sent quantity_, the sent quantity must exclude the fee's quantity.
> * Example #1: If you send £50 to an exchange and spend it all in a transaction that incurs a £0.50 fee, the sent quantity is 49.5.
> * Example #2: If you swap 1 bitcoin for 10 ethers on an exchange and the transaction incurs a BTC 0.01 fee, the sent quantity is 0.99.

#### Sent asset is non-fungible

Whether the sent asset is an NFT. This is a boolean value that can be any of `true`, `yes`, `y`, and `1`. If your spreadsheet software allows you to insert checkboxes in your cells, use that.

### Received asset

A received asset must be specified for `receive` and `swap` transactions.

Here is the detail of each related column.

#### Received asset

This is the received asset's symbol, or ticker (e.g. `BTC` for Bitcoin or `GBP` for pound sterling).

If the asset is an NFT, you can use any string value you like, so long as it is unique in your spreadsheet. I usually go for the collection's ID followed by the item's number (e.g. [this CryptoPunk](https://opensea.io/assets/ethereum/0xb47e3cd837ddf8e4c57f05d70ab865de6e193bbb/1008) would be ID `0xb47e3cd837ddf8e4c57f05d70ab865de6e193bbb/1008`).

#### Received quantity

The quantity received. For NFTs, that would be `1`.

Be as precise as possible and report all available decimal places.

#### Received asset is non-fungible

Whether the received asset is an NFT. This is a boolean value that can be any of `true`, `yes`, `y`, and `1`. If your spreadsheet software allows you to insert checkboxes in your cells, use that.

### Fee

While the related values are optional, most transactions will incur a fee.

Different values must be used depending on the context:

* If the transaction is on-chain (e.g. performed through a decentralised protocol), use the blockchain's fee (i.e. as reported by the block explorer);
* If the transaction was conducted on an exchange, use the exchange's fee, even if there is a corresponding on-chain transaction.

The reason for the latter is that exchanges usually estimate how much the fee will be and charge that estimate. This amount is almost always different from the actual fee, but that's the amount you will be charged anyway.

> **Note**
> Be sure not to report fees that you didn't pay for. For instance, if you received an airdrop that was sent by the token issuer and the transaction includes a fee, the issuer paid for that fee, not you, so you don't have to report it.

Here is the detail of each fee-related column.

#### Fee currency

This is the symbol (or ticker) of the asset used to pay for the fee (e.g. `BTC` for Bitcoin or `GBP` for pound sterling).

#### Fee quantity

The quantity used for the fee.

Be as precise as possible and report all available decimal places.

#### Fee market value

The market value of a fee is its value expressed in pound sterling at the time of the transaction. See [Market value](#market-value) for details.

### Income

Some transactions are considered income by HMRC. A typical example is an [airdrop](https://www.gov.uk/hmrc-internal-manuals/cryptoassets-manual/crypto21250) received because of some past activity.

For instance, if you had purchased an [ENS domain](https://ens.domains/) before November 2021, you would have received some ENS tokens as part of their airdrop. This was a reward based on something done in the past, which is considered income by HMRC.

The `Income` column is a boolean value that can be any of `true`, `yes`, `y`, and `1`. If your spreadsheet software allows you to insert checkboxes in your cells, use that.

### Summary

Here is a summary of the important rules:

* Always use the sent asset's market value when available, even if the received asset is a fiat amount;
* Transfers don't need a market value;
* The sent quantity must exclude the fee's quantity when the sent asset and the fee's currency are the same;
* The sent asset's market value must be based on the reported sent quantity;
* In `receive` transactions, the market value is the received asset's market value;
* Use HMRC's [exchange rates](https://www.gov.uk/government/publications/hmrc-exchange-rates-for-2023-monthly "HMRC exchange rates for 2023: monthly") to convert foreign currency amounts to pound sterling.

If you got this far you must have realised by now that reporting transactions is a bit of work. Dime grants you more control over your reporting but the trade-off is that it is your responsibility to do it well.

In other words, the returned tax figures are only as good as the spreadsheet's accuracy.

### Special cases

Here is a list of edge cases that need to be reported in a certain way to be correctly processed.

#### Minting an NFT out of several other NFTs

<details>
<summary>Detail</summary>

Some collections allow you to collect NFTs that you can then combine to mint a new NFT through a _forge_. In practice, that means exchanging several NFTs for another one in a single transaction. NFTs being unique, there is no easy way to report such a transaction as a single spreadsheet row.

The way to do this in Dime is to split the transaction into as many transactions as there are NFTs involved in the creation of the new one.

Imagine you buy three different NFTs depicting animals. One is a lion (market value of £200), another one is a goat (£100), and the third one is a snake (£150). You then combine them to mint a new NFT depicting the Chimera, effectively swapping the three initial NFTs for the new one.

Instead of reporting a single transaction, you'd report the following transactions in your spreadsheet:

* Swap the lion NFT for the Chimera NFT (market value of £200);
* Swap the goat NFT for the Chimera NFT (market value of £100);
* Swap the snake NFT for the Chimera NFT (market value of £150).

While expressed as three different transactions, Dime will identify that in each case the Chimera NFT is being received, and will update its cost basis accordingly (the total amount here being £450).
</details>

## How it works

Dime processes the transactions contained in your spreadsheet and saves them in a local [SQLite](https://sqlite.org/index.html) database along with the tax figures that it computes.

The database file is located within your user directory's `.dime` folder:

```
$ ls ~/.dime/database.sqlite
```

Everything is processed locally – your transactions never leave your computer.

A technical description of the architecture is available [here](https://tech.osteel.me/posts/building-a-php-cli-tool-using-ddd-and-event-sourcing-software-design "Building a PHP CLI tool using DDD and Event Sourcing: software design").

## Maintenance

Upgrading and deleting Dime depends on how you installed it in the first place.

### Upgrade

If you have installed Dime [via Composer](#composer), this is how to upgrade to a minor version (e.g. `v1.1`):

```
$ composer global update osteel/dime
```

To a major version (e.g. `v2.0`):

```
$ composer global require osteel/dime
```

If you have installed Dime by downloading the [PHAR archive](#phar-linux--unix--macos):

```
$ dime self-update
```

### Delete

If you have installed Dime [via Composer](#composer):

```
$ composer global remove osteel/dime
```

If you have installed Dime by downloading the [PHAR archive](#phar-linux--unix--macos), you need to manually delete it from its location, e.g.:

```
$ rm /usr/local/bin/dime
```

**In both cases**, you also need to delete the `.dime` folder (that contains the database):

```
$ rm -r ~/.dime
```

## Issue reporting

I don't take feature requests at this stage (although I will look at pull requests – see [Contributing](#contributing) below), but I will try and address bugs as well as any issue related to reported figures.

If Dime seemingly crashes for no reason or returns numbers that appear to be wrong, please file an issue [here](https://github.com/osteel/dime/issues) using the relevant template.

Also, feel free to open a [discussion](https://github.com/osteel/dime/discussions) for anything else related to the program (e.g. tax rules, how to report something in the spreadsheet, etc.). I cannot guarantee that I will personally reply, but someone else might.

## Contributing

I don't take feature requests at this stage but pull requests are welcome. See [CONTRIBUTING](/.github/CONTRIBUTING.md) for details.

Also, make sure to open a [discussion](https://github.com/osteel/dime/discussions) before you take on any significant work to avoid disappointment.